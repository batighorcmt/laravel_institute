// CS8602 is suppressed for this file because all _sdk usages are guarded by
// explicit null checks ("if (_sdk == null) return") before use.
// The warning is a COM Interop false-positive — the SDK object cannot be null
// at the point where it is used.
#pragma warning disable CS8602

using System;
using System.Collections.Concurrent;
using System.Collections.Generic;
using System.Runtime.InteropServices;
using System.Threading;
using System.Threading.Tasks;

namespace BiometricAgent
{
    // ─────────────────────────────────────────────────────────────────────────
    // Adapter Pattern: IBiometricAdapter defines the contract.
    // ZKTecoAdapter wraps the ZKEMKEEPER SDK COM object.
    // ─────────────────────────────────────────────────────────────────────────

    public interface IBiometricAdapter
    {
        bool Connect(string ip, int port, int machineNumber, bool registerEvents = true);
        void Disconnect();
        bool IsConnected { get; }
        /// <summary>Cheap round-trip to the device to detect a connection that has silently
        /// dropped since Connect() (IsConnected alone never flips back to false on its own).
        /// Attempts to reconnect if the probe fails. Returns the up-to-date connection state.</summary>
        bool CheckConnection();
        List<PunchRecord> ReadNewAttendanceLogs();
        bool ClearAttendanceLog();
        List<BiometricTemplate> ReadAllTemplates(Action<int>? onProgress = null);
        bool UploadTemplate(BiometricTemplate template);
        string GetSerialNumber();
        string Brand { get; }
        string LastError { get; }
    }

    internal abstract class StaTaskQueue : IDisposable
    {
        private readonly BlockingCollection<Action> _actions = new();
        private readonly Thread _thread;
        private bool _disposed;

        protected StaTaskQueue(string name)
        {
            _thread = new Thread(Run)
            {
                IsBackground = true,
                Name = name
            };
            _thread.SetApartmentState(ApartmentState.STA);
            _thread.Start();
        }

        public Task Enqueue(Action action)
        {
            if (_disposed) throw new ObjectDisposedException(nameof(StaTaskQueue));
            var tcs = new TaskCompletionSource<object?>(TaskCreationOptions.RunContinuationsAsynchronously);
            _actions.Add(() =>
            {
                try
                {
                    action();
                    tcs.SetResult(null);
                }
                catch (Exception ex)
                {
                    tcs.SetException(ex);
                }
            });
            return tcs.Task;
        }

        public Task<T> Enqueue<T>(Func<T> func)
        {
            if (_disposed) throw new ObjectDisposedException(nameof(StaTaskQueue));
            var tcs = new TaskCompletionSource<T>(TaskCreationOptions.RunContinuationsAsynchronously);
            _actions.Add(() =>
            {
                try
                {
                    tcs.SetResult(func());
                }
                catch (Exception ex)
                {
                    tcs.SetException(ex);
                }
            });
            return tcs.Task;
        }

        private void Run()
        {
            foreach (var action in _actions.GetConsumingEnumerable())
            {
                action();
            }
        }

        public void Dispose()
        {
            if (_disposed) return;
            _disposed = true;
            _actions.CompleteAdding();
            _thread.Join();
            _actions.Dispose();
        }
    }

    internal sealed class ZKStaQueue : StaTaskQueue
    {
        public ZKStaQueue() : base("ZKStaQueue") { }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ZKTeco Adapter – uses ZKEMKEEPER SDK DLL (zkemkeeper.dll in /Sdk folder)
    // ─────────────────────────────────────────────────────────────────────────
    public class ZKTecoAdapter : IBiometricAdapter, IDisposable
    {
        public string Brand => "ZKTeco";

        // The COM object: requires zkemkeeper.dll registered or placed in exe folder
        private dynamic? _sdk;
        private readonly ZKStaQueue _staQueue = new();
        private bool _isConnected;
        public bool IsConnected => _isConnected;
        private int _machineNumber = 1;
        private const string ProgId = "zkemkeeper.ZKEM.1";
        private string _lastError = string.Empty;
        public string LastError => _lastError;

        public ZKTecoAdapter()
        {
            // Do not create the COM object here.
            // It must be instantiated on the STA thread that will use it.
        }

        private bool EnsureSdkInitialized()
        {
            if (_sdk != null)
                return true;

            return _staQueue.Enqueue(() =>
            {
                try
                {
                    var type = Type.GetTypeFromProgID(ProgId);
                    if (type == null)
                    {
                        _lastError = "zkemkeeper COM ProgID not registered or wrong SDK bitness.";
                        return false;
                    }

                    _sdk = Activator.CreateInstance(type);
                    if (_sdk == null)
                    {
                        _lastError = "Failed to instantiate zkemkeeper COM object.";
                        return false;
                    }

                    return true;
                }
                catch (Exception ex)
                {
                    _sdk = null;
                    _lastError = $"Failed to initialize ZKTeco SDK: {ex.Message}";
                    return false;
                }
            }).Result;
        }

        public bool Connect(string ip, int port, int machineNumber, bool registerEvents = true)
        {
            _machineNumber = machineNumber;
            if (!EnsureSdkInitialized())
                return false;

            try
            {
                return _staQueue.Enqueue(() =>
                {
                    try
                    {
                        // This device only tolerates ONE active command connection at a
                        // time - a second simultaneous connection doesn't get refused, but
                        // every write on either session then silently fails (confirmed:
                        // SSR_SetUserInfo returns SDK error -2 while two sessions are open,
                        // succeeds immediately once only one exists). If we're already
                        // connected, releasing that socket first guarantees this call never
                        // leaves a stale duplicate session behind.
                        if (_isConnected)
                        {
                            try { _sdk.Disconnect(); } catch { }
                            _isConnected = false;
                        }

                        bool connected = _sdk.Connect_Net(ip, port);
                        if (connected)
                        {
                            // Real-time event callbacks require the owning STA thread to pump
                            // Windows messages. Our worker thread only runs queued actions with
                            // no message loop, so registering events on a connection that never
                            // pumps messages (e.g. the short-lived template-uploader session)
                            // leaves the SDK trying to deliver a callback with nowhere to go,
                            // which manifests as a native access violation during later calls.
                            if (registerEvents)
                                _sdk.RegEvent(_machineNumber, 65535); // Register all real-time events
                            _isConnected = true;
                            _lastError = string.Empty;
                        }
                        else
                        {
                            _isConnected = false;
                            int lastError = 0;
                            try
                            {
                                _sdk.GetLastError(out lastError);
                            }
                            catch { }
                            _lastError = lastError != 0
                                ? $"Connect_Net returned false (SDK error {lastError}). Check IP address, port and device network settings."
                                : "Connect_Net returned false. Check IP address, port and device network settings.";
                        }

                        return connected;
                    }
                    catch (Exception ex)
                    {
                        _isConnected = false;
                        int lastError = 0;
                        try
                        {
                            _sdk.GetLastError(out lastError);
                        }
                        catch { }
                        _lastError = lastError != 0
                            ? $"Connect_Net failed: {ex.Message} (SDK error {lastError})"
                            : $"Connect_Net failed: {ex.Message}";
                        return false;
                    }
                }).Result;
            }
            catch (Exception ex)
            {
                _isConnected = false;
                _lastError = $"Adapter connect error: {ex.Message}";
                return false;
            }
        }

        public void Disconnect()
        {
            if (_sdk == null || !_isConnected) return;
            try
            {
                _staQueue.Enqueue(() =>
                {
                    try { _sdk?.Disconnect(); }
                    catch { }
                    _isConnected = false;
                }).Wait();
            }
            catch { _isConnected = false; }
        }

        public string GetSerialNumber()
        {
            if (_sdk == null || !_isConnected) return "";
            try
            {
                return _staQueue.Enqueue(() =>
                {
                    _sdk.GetSerialNumber(_machineNumber, out string sn);
                    return sn?.Trim() ?? string.Empty;
                }).Result;
            }
            catch { return ""; }
        }

        public bool CheckConnection()
        {
            // IsConnected only reflects the last explicit Connect()/Disconnect() call - a
            // device that drops mid-session still reports IsConnected == true forever, since
            // nothing else flips it back. This does a cheap round-trip to catch that case
            // and immediately reflects the real state (used by the fast connectivity timer).
            if (_sdk == null || !_isConnected) return false;

            try
            {
                var task = _staQueue.Enqueue(() =>
                {
                    try
                    {
                        _sdk.GetSerialNumber(_machineNumber, out string sn);
                        return true;
                    }
                    catch
                    {
                        return false;
                    }
                });

                if (!task.Wait(TimeSpan.FromSeconds(10)))
                {
                    _lastError = "Connectivity probe timed out.";
                    _isConnected = false;
                    return false;
                }

                _isConnected = task.Result;
                return _isConnected;
            }
            catch
            {
                _isConnected = false;
                return false;
            }
        }

        public List<PunchRecord> ReadNewAttendanceLogs()
        {
            if (_sdk == null || !_isConnected) return new List<PunchRecord>();

            try
            {
                return _staQueue.Enqueue(() =>
                {
                    var punches = new List<PunchRecord>();
                    _sdk.ReadGeneralLogData(_machineNumber);

                    int dwVerifyMode = 0, dwInOutMode = 0;
                    int dwYear = 0, dwMonth = 0, dwDay = 0;
                    int dwHour = 0, dwMinute = 0, dwSecond = 0, dwWorkcode = 0;

                    while (_sdk.SSR_GetGeneralLogData(
                        _machineNumber,
                        out string enrollNo,
                        out dwVerifyMode,
                        out dwInOutMode,
                        out dwYear,
                        out dwMonth,
                        out dwDay,
                        out dwHour,
                        out dwMinute,
                        out dwSecond,
                        ref dwWorkcode))
                    {
                        var punchTime = new DateTime(dwYear, dwMonth, dwDay, dwHour, dwMinute, dwSecond);
                        string punchType = dwInOutMode switch
                        {
                            0 => "check_in",
                            1 => "check_out",
                            2 => "break_out",
                            3 => "break_in",
                            4 => "overtime_in",
                            5 => "overtime_out",
                            _ => "check_in"
                        };

                        punches.Add(new PunchRecord
                        {
                            BiometricId = enrollNo.Trim(),
                            PunchTime = punchTime.ToString("yyyy-MM-dd HH:mm:ss"),
                            PunchType = punchType
                        });
                    }

                    return punches;
                }).Result;
            }
            catch { return new List<PunchRecord>(); }
        }

        public bool ClearAttendanceLog()
        {
            if (_sdk == null || !_isConnected) return false;

            try
            {
                return _staQueue.Enqueue(() =>
                {
                    bool success = _sdk.ClearGLog(_machineNumber);
                    _sdk.RefreshData(_machineNumber);
                    return success;
                }).Result;
            }
            catch { return false; }
        }

        public List<BiometricTemplate> ReadAllTemplates(Action<int>? onProgress = null)
        {
            if (_sdk == null || !_isConnected) return new List<BiometricTemplate>();

            try
            {
                // A large roster (1000+ users) genuinely takes many minutes to read over
                // this SDK's per-user/per-finger polling API (~1-1.5s/user measured) - a
                // flat 5-minute timeout was firing on reads that were still progressing
                // normally. Track *inactivity* instead of total elapsed time: only bail if
                // no progress has been made for a while (a real stall), and keep a much
                // larger absolute ceiling as a final safety net.
                long lastProgressTicks = Environment.TickCount64;
                int abandoned = 0;

                var task = _staQueue.Enqueue(() =>
                {
                    var templates = new List<BiometricTemplate>();
                    _sdk.ReadAllTemplate(_machineNumber);

                    int processedUsers = 0;
                    while (_sdk.SSR_GetAllUserInfo(_machineNumber, out string enrollNo, out string name, out string password, out int privilege, out bool enabled))
                    {
                        for (int fingerIndex = 0; fingerIndex < 10; fingerIndex++)
                        {
                            string tmpData = string.Empty;
                            int tmpLength = 0;
                            if (_sdk.SSR_GetUserTmpStr(_machineNumber, enrollNo, fingerIndex, out tmpData, out tmpLength))
                            {
                                templates.Add(new BiometricTemplate
                                {
                                    BiometricId = enrollNo.Trim(),
                                    FingerIndex = fingerIndex,
                                    TemplateData = tmpData,
                                    Privilege = privilege,
                                    Name = name
                                });
                            }
                        }

                        processedUsers++;
                        Interlocked.Exchange(ref lastProgressTicks, Environment.TickCount64);
                        onProgress?.Invoke(processedUsers);

                        // The caller gave up (inactivity or absolute ceiling tripped) -
                        // stop doing pointless work instead of running to completion
                        // invisibly in the background.
                        if (Volatile.Read(ref abandoned) != 0)
                            return templates;
                    }

                    return templates;
                });

                const int pollIntervalMs = 5000;
                const int inactivityTimeoutMs = 90_000;      // no progress for 90s = genuinely stuck
                const int hardCeilingMs = 90 * 60_000;        // 90 minutes absolute max regardless of progress
                var sw = System.Diagnostics.Stopwatch.StartNew();

                while (!task.Wait(pollIntervalMs))
                {
                    long idleMs = Environment.TickCount64 - Interlocked.Read(ref lastProgressTicks);
                    if (idleMs > inactivityTimeoutMs)
                    {
                        _lastError = $"Reading templates stalled - no progress for {idleMs / 1000}s. Try 'Refresh Devices'.";
                        _isConnected = false;
                        Volatile.Write(ref abandoned, 1);
                        return new List<BiometricTemplate>();
                    }
                    if (sw.ElapsedMilliseconds > hardCeilingMs)
                    {
                        _lastError = "Reading templates exceeded the maximum allowed time (90 minutes). Try 'Refresh Devices'.";
                        _isConnected = false;
                        Volatile.Write(ref abandoned, 1);
                        return new List<BiometricTemplate>();
                    }
                }

                return task.Result;
            }
            catch { return new List<BiometricTemplate>(); }
        }

        public bool UploadTemplate(BiometricTemplate template)
        {
            if (_sdk == null || !_isConnected) return false;

            try
            {
                return _staQueue.Enqueue(() =>
                {
                    // NOTE: an earlier version wrapped this in EnableDevice(false)/(true)
                    // ("disable device while writing" is common SDK advice). In practice,
                    // on this hardware it made things dramatically worse - toggling the
                    // device on/off around every single call, back-to-back for 1000+ users,
                    // drove SSR_SetUserInfo to fail almost universally. Removed.

                    // SSR_SetUserInfo's result was previously discarded - if it fails,
                    // the enrollNo record never gets (re)created, and SetUserTmpStr will
                    // then reliably fail too since it has no user record to attach to.
                    bool userOk = _sdk.SSR_SetUserInfo(_machineNumber, template.BiometricId, template.Name ?? string.Empty, string.Empty, template.Privilege, true);
                    if (!userOk)
                    {
                        _lastError = DescribeSdkFailure("SSR_SetUserInfo");
                        return false;
                    }

                    bool tmplOk = _sdk.SetUserTmpStr(_machineNumber, template.BiometricId, template.FingerIndex, template.TemplateData);
                    if (!tmplOk)
                        _lastError = DescribeSdkFailure("SetUserTmpStr");
                    return tmplOk;
                }).Result;
            }
            catch (Exception ex)
            {
                _lastError = $"UploadTemplate exception: {ex.Message}";
                return false;
            }
        }

        private string DescribeSdkFailure(string apiName)
        {
            try
            {
                int code = 0;
                _sdk.GetLastError(out code);
                return $"{apiName} returned false (SDK error {code}).";
            }
            catch
            {
                return $"{apiName} returned false.";
            }
        }

        public void Dispose()
        {
            try
            {
                _staQueue?.Dispose();
            }
            catch { }
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Placeholder for future adapters (Hikvision, Suprema, etc.)
    // ─────────────────────────────────────────────────────────────────────────
    public class HikvisionAdapter : IBiometricAdapter
    {
        public string Brand => "Hikvision";
        public bool IsConnected => false;
        public string LastError => "";
        public bool Connect(string ip, int port, int machineNumber, bool registerEvents = true) => throw new NotImplementedException("Hikvision adapter coming soon.");
        public void Disconnect() => throw new NotImplementedException();
        public bool CheckConnection() => throw new NotImplementedException();
        public List<PunchRecord> ReadNewAttendanceLogs() => throw new NotImplementedException();
        public bool ClearAttendanceLog() => throw new NotImplementedException();
        public List<BiometricTemplate> ReadAllTemplates(Action<int>? onProgress = null) => throw new NotImplementedException();
        public bool UploadTemplate(BiometricTemplate template) => throw new NotImplementedException();
        public string GetSerialNumber() => throw new NotImplementedException();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Factory: creates the right adapter based on device brand
    // ─────────────────────────────────────────────────────────────────────────
    public static class BiometricAdapterFactory
    {
        public static IBiometricAdapter Create(string brand)
        {
            return brand.ToLower() switch
            {
                "zkteco" => new ZKTecoAdapter(),
                _        => throw new NotSupportedException($"Brand '{brand}' not supported yet.")
            };
        }
    }
}

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
        bool Connect(string ip, int port);
        void Disconnect();
        bool IsConnected { get; }
        List<PunchRecord> ReadNewAttendanceLogs();
        List<BiometricTemplate> ReadAllTemplates();
        bool UploadTemplate(BiometricTemplate template);
        string GetSerialNumber();
        string Brand { get; }
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
                        return false;

                    _sdk = Activator.CreateInstance(type);
                    return _sdk != null;
                }
                catch
                {
                    _sdk = null;
                    return false;
                }
            }).Result;
        }

        public bool Connect(string ip, int port)
        {
            if (!EnsureSdkInitialized())
                return false;

            try
            {
                return _staQueue.Enqueue(() =>
                {
                    bool connected = _sdk.Connect_Net(ip, port);
                    if (connected)
                    {
                        _sdk.RegEvent(_machineNumber, 65535); // Register all real-time events
                        _isConnected = true;
                    }
                    else
                    {
                        _isConnected = false;
                    }
                    return connected;
                }).Result;
            }
            catch
            {
                _isConnected = false;
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

        public List<BiometricTemplate> ReadAllTemplates()
        {
            if (_sdk == null || !_isConnected) return new List<BiometricTemplate>();

            try
            {
                return _staQueue.Enqueue(() =>
                {
                    var templates = new List<BiometricTemplate>();
                    _sdk.ReadAllTemplate(_machineNumber);

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
                    }

                    return templates;
                }).Result;
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
                    _sdk.SSR_SetUserInfo(_machineNumber, template.BiometricId, template.Name ?? string.Empty, string.Empty, template.Privilege, true);
                    return _sdk.SetUserTmpStr(_machineNumber, template.BiometricId, template.FingerIndex, template.TemplateData);
                }).Result;
            }
            catch { return false; }
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
        public bool Connect(string ip, int port) => throw new NotImplementedException("Hikvision adapter coming soon.");
        public void Disconnect() => throw new NotImplementedException();
        public List<PunchRecord> ReadNewAttendanceLogs() => throw new NotImplementedException();
        public List<BiometricTemplate> ReadAllTemplates() => throw new NotImplementedException();
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

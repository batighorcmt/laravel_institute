// CS8602 is suppressed for this file because all _sdk usages are guarded by
// explicit null checks ("if (_sdk == null) return") before use.
// The warning is a COM Interop false-positive — the SDK object cannot be null
// at the point where it is used.
#pragma warning disable CS8602

using System;
using System.Collections.Generic;
using System.Runtime.InteropServices;
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
        string Brand { get; }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ZKTeco Adapter – uses ZKEMKEEPER SDK DLL (zkemkeeper.dll in /Sdk folder)
    // ─────────────────────────────────────────────────────────────────────────
    public class ZKTecoAdapter : IBiometricAdapter
    {
        public string Brand => "ZKTeco";

        // The COM object: requires zkemkeeper.dll registered or placed in exe folder
        private dynamic? _sdk;
        private bool _isConnected;
        public bool IsConnected => _isConnected;
        private int _machineNumber = 1;

        public ZKTecoAdapter()
        {
            // Create via late binding to avoid needing the COM reference at compile time
            // This allows the agent to run even if the SDK DLL isn't registered
            try
            {
                var type = Type.GetTypeFromProgID("zkemkeeper.ZKEM.1");
                if (type != null)
                    _sdk = Activator.CreateInstance(type);
            }
            catch { _sdk = null; }
        }

        public bool Connect(string ip, int port)
        {
            if (_sdk == null) return false;
            try
            {
                _isConnected = _sdk.Connect_Net(ip, port);
                if (_isConnected)
                {
                    _sdk.RegEvent(_machineNumber, 65535); // Register all real-time events
                }
                return _isConnected;
            }
            catch { return false; }
        }

        public void Disconnect()
        {
            if (_sdk == null || !_isConnected) return;
            try { _sdk?.Disconnect(); }
            catch { }
            _isConnected = false;
        }

        public List<PunchRecord> ReadNewAttendanceLogs()
        {
            var punches = new List<PunchRecord>();
            if (_sdk == null || !_isConnected) return punches;
            var sdk = _sdk; // non-null after guard above

            try
            {
                sdk.ReadGeneralLogData(_machineNumber);

                int dwVerifyMode = 0, dwInOutMode = 0;
                int dwYear = 0, dwMonth = 0, dwDay = 0;
                int dwHour = 0, dwMinute = 0, dwSecond = 0, dwWorkcode = 0;

                // Iteratively fetch all buffered logs
                while (sdk.SSR_GetGeneralLogData(
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
                        PunchTime   = punchTime.ToString("yyyy-MM-dd HH:mm:ss"),
                        PunchType   = punchType
                    });
                }

                // Clear logs from device to prevent re-reading (optional – configure per school)
                // _sdk.ClearGLog(_machineNumber);
            }
            catch { /* Log if needed */ }

            return punches;
        }

        public List<BiometricTemplate> ReadAllTemplates()
        {
            var templates = new List<BiometricTemplate>();
            if (_sdk == null || !_isConnected) return templates;
            var sdk = _sdk;

            try
            {
                sdk.ReadAllTemplate(_machineNumber);
                
                string enrollNo = "";
                string name = "";
                string password = "";
                int privilege = 0;
                bool enabled = false;

                while (sdk.SSR_GetAllUserInfo(_machineNumber, out enrollNo, out name, out password, out privilege, out enabled))
                {
                    // Read fingers (0-9)
                    for (int fingerIndex = 0; fingerIndex < 10; fingerIndex++)
                    {
                        string tmpData = "";
                        int tmpLength = 0;
                        if (sdk.SSR_GetUserTmpStr(_machineNumber, enrollNo, fingerIndex, out tmpData, out tmpLength))
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
            }
            catch { }

            return templates;
        }

        public bool UploadTemplate(BiometricTemplate template)
        {
            if (_sdk == null || !_isConnected) return false;
            var sdk = _sdk;
            try
            {
                sdk.SSR_SetUserInfo(_machineNumber, template.BiometricId, template.Name ?? "", "", template.Privilege, true);
                bool success = sdk.SetUserTmpStr(_machineNumber, template.BiometricId, template.FingerIndex, template.TemplateData);
                return success;
            }
            catch { return false; }
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

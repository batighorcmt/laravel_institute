using System;
using System.Collections.Generic;
using System.Net.Http;
using System.Net.Http.Headers;
using System.Text;
using System.Threading.Tasks;
using Newtonsoft.Json;

namespace BiometricAgent
{
    // ─────────────────────────────────────────────────────────────────────────
    // Communicates with the Laravel SaaS API
    // ─────────────────────────────────────────────────────────────────────────
    public class CloudSyncManager
    {
        private readonly AgentConfig _config;
        private readonly OfflineQueue _queue;
        private readonly HttpClient _http;

        public CloudSyncManager(AgentConfig config, OfflineQueue queue)
        {
            _config = config;
            _queue  = queue;
            _http   = new HttpClient { Timeout = TimeSpan.FromSeconds(15) };
        }

        /// <summary>Set Bearer token after agent login.</summary>
        public void SetToken(string token)
        {
            _http.DefaultRequestHeaders.Authorization =
                new AuthenticationHeaderValue("Bearer", token);
        }

        /// <summary>Login the agent to the SaaS and store token.</summary>
        public async Task<bool> AuthenticateAsync()
        {
            try
            {
                var body = JsonConvert.SerializeObject(new
                {
                    school_code = _config.SchoolCode,
                    agent_token = _config.AgentToken
                });
                var resp = await _http.PostAsync(
                    $"{_config.SaasApiUrl}/biometric/agent/login",
                    new StringContent(body, Encoding.UTF8, "application/json"));

                if (resp.IsSuccessStatusCode)
                {
                    var result = JsonConvert.DeserializeObject<dynamic>(
                        await resp.Content.ReadAsStringAsync());
                    string token = result?.token ?? "";
                    SetToken(token);
                    return true;
                }
                return false;
            }
            catch { return false; }
        }

        /// <summary>Send agent heartbeat to SaaS.</summary>
        public async Task<bool> SendAgentHeartbeatAsync()
        {
            try
            {
                var resp = await _http.PostAsync(
                    $"{_config.SaasApiUrl}/biometric/agent/heartbeat",
                    new StringContent("", Encoding.UTF8, "application/json"));
                return resp.IsSuccessStatusCode;
            }
            catch { return false; }
        }

        /// <summary>Send heartbeat for a single device.</summary>
        public async Task<bool> SendHeartbeatAsync(
            int schoolId, string serial, string status, string ip)
        {
            try
            {
                var body = JsonConvert.SerializeObject(new
                {
                    school_id     = schoolId,
                    device_serial = serial,
                    status,
                    ip_address    = ip
                });
                var resp = await _http.PostAsync(
                    $"{_config.SaasApiUrl}/biometric/device/heartbeat",
                    new StringContent(body, Encoding.UTF8, "application/json"));
                return resp.IsSuccessStatusCode;
            }
            catch { return false; }
        }

        /// <summary>Send punch logs directly to the SaaS. If offline, enqueues them.</summary>
        public async Task SyncAttendanceAsync(
            int schoolId, string deviceSerial, List<PunchRecord> punches)
        {
            try
            {
                var body = JsonConvert.SerializeObject(new
                {
                    school_id     = schoolId,
                    device_serial = deviceSerial,
                    logs          = punches
                });
                var resp = await _http.PostAsync(
                    $"{_config.SaasApiUrl}/biometric/attendance/sync",
                    new StringContent(body, Encoding.UTF8, "application/json"));

                if (!resp.IsSuccessStatusCode)
                {
                    // If server error, queue for retry
                    _queue.Enqueue(schoolId, deviceSerial, punches);
                }
            }
            catch (Exception)
            {
                // Internet unavailable — save locally
                _queue.Enqueue(schoolId, deviceSerial, punches);
            }
        }

        /// <summary>Retry all pending offline queue items.</summary>
        public async Task RetryOfflineQueueAsync()
        {
            var pending = _queue.GetPending();
            foreach (var item in pending)
            {
                try
                {
                    var body = JsonConvert.SerializeObject(new
                    {
                        school_id     = item.SchoolId,
                        device_serial = item.DeviceSerial,
                        logs          = item.Data
                    });
                    var resp = await _http.PostAsync(
                        $"{_config.SaasApiUrl}/biometric/attendance/sync",
                        new StringContent(body, Encoding.UTF8, "application/json"));

                    if (resp.IsSuccessStatusCode)
                        _queue.MarkSuccess(item.Id);
                    else
                        _queue.IncrementRetry(item.Id);
                }
                catch
                {
                    _queue.IncrementRetry(item.Id);
                }
            }
        }
        /// <summary>Upload templates from device to Web DB.</summary>
        public async Task<bool> SyncTemplatesUpAsync(int schoolId, string deviceSerial, List<BiometricTemplate> templates)
        {
            try
            {
                var body = JsonConvert.SerializeObject(new
                {
                    school_id = schoolId,
                    device_serial = deviceSerial,
                    templates = templates
                });
                var resp = await _http.PostAsync(
                    $"{_config.SaasApiUrl}/biometric/templates/upload",
                    new StringContent(body, Encoding.UTF8, "application/json"));
                return resp.IsSuccessStatusCode;
            }
            catch { return false; }
        }

        /// <summary>Download templates from Web DB.</summary>
        public async Task<List<BiometricTemplate>> SyncTemplatesDownAsync(int schoolId, string deviceSerial)
        {
            try
            {
                var body = JsonConvert.SerializeObject(new
                {
                    school_id = schoolId,
                    device_serial = deviceSerial
                });
                var resp = await _http.PostAsync(
                    $"{_config.SaasApiUrl}/biometric/templates/download",
                    new StringContent(body, Encoding.UTF8, "application/json"));

                if (resp.IsSuccessStatusCode)
                {
                    var json = await resp.Content.ReadAsStringAsync();
                    var result = JsonConvert.DeserializeObject<dynamic>(json);
                    var list = JsonConvert.DeserializeObject<List<BiometricTemplate>>(Convert.ToString(result?.templates));
                    return list ?? new List<BiometricTemplate>();
                }
            }
            catch { }
            return new List<BiometricTemplate>();
        }

        /// <summary>Fetch all users (students & teachers) from the SaaS.</summary>
        public async Task<List<UserRecord>> GetUsersAsync(int schoolId)
        {
            try
            {
                var resp = await _http.GetAsync(
                    $"{_config.SaasApiUrl}/biometric/users?school_id={schoolId}");
                if (resp.IsSuccessStatusCode)
                {
                    var json = await resp.Content.ReadAsStringAsync();
                    var result = JsonConvert.DeserializeObject<dynamic>(json);
                    var list = JsonConvert.DeserializeObject<List<UserRecord>>(Convert.ToString(result?.users));
                    return list ?? new List<UserRecord>();
                }
            }
            catch { }
            return new List<UserRecord>();
        }

        /// <summary>Check if a newer version of the agent is available.</summary>
        public async Task<UpdateInfo?> CheckForUpdateAsync()
        {
            try
            {
                // Use a fresh HttpClient (no Bearer token needed for this public endpoint)
                using var http = new HttpClient { Timeout = TimeSpan.FromSeconds(10) };
                var resp = await http.GetAsync($"{_config.SaasApiUrl}/biometric/agent/check-update");
                if (resp.IsSuccessStatusCode)
                {
                    var json = await resp.Content.ReadAsStringAsync();
                    return JsonConvert.DeserializeObject<UpdateInfo>(json);
                }
            }
            catch { }
            return null;
        }
    }

    // ── DTOs ──────────────────────────────────────────────────────────────────
    public class UserRecord
    {
        [JsonProperty("biometric_id")]
        public string BiometricId { get; set; } = "";

        [JsonProperty("name")]
        public string Name { get; set; } = "";

        [JsonProperty("role")]
        public string Role { get; set; } = "";
    }

    public class UpdateInfo
    {
        [JsonProperty("latest_version")]
        public string LatestVersion { get; set; } = "";

        [JsonProperty("download_url")]
        public string DownloadUrl { get; set; } = "";

        [JsonProperty("release_notes")]
        public string ReleaseNotes { get; set; } = "";

        [JsonProperty("mandatory")]
        public bool Mandatory { get; set; }
    }
}

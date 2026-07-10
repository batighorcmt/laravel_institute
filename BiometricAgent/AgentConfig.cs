using System;
using System.Collections.Generic;
using System.IO;
using System.Net.Http;
using System.Net.Http.Headers;
using System.Text;
using System.Threading.Tasks;
using Microsoft.Data.Sqlite;
using Newtonsoft.Json;

namespace BiometricAgent
{
    // ─────────────────────────────────────────────────────────────────────────
    // Configuration persisted to agent_config.json in app directory
    // ─────────────────────────────────────────────────────────────────────────
    public class AgentConfig
    {
        public string SaasApiUrl { get; set; } = "https://your-school-saas.com/api";
        public string SchoolCode { get; set; } = "";
        public string AgentToken { get; set; } = "";
        public int SyncIntervalSeconds { get; set; } = 60;
        public bool AutoStart { get; set; } = true;
        public string MachineName { get; set; } = Environment.MachineName;
        public List<DeviceConfig> Devices { get; set; } = new();

        private static readonly string ConfigPath =
            Path.Combine(AppDomain.CurrentDomain.BaseDirectory, "agent_config.json");

        public static AgentConfig Load()
        {
            if (!File.Exists(ConfigPath))
                return new AgentConfig();

            try
            {
                var json = File.ReadAllText(ConfigPath);
                var config = JsonConvert.DeserializeObject<AgentConfig>(json) ?? new AgentConfig();

                if (!string.Equals(config.MachineName, Environment.MachineName, StringComparison.OrdinalIgnoreCase))
                {
                    // New machine or moved install: keep credentials but clear stale per-machine devices.
                    config.MachineName = Environment.MachineName;
                    config.Devices = new List<DeviceConfig>();
                    config.Save();
                }

                return config;
            }
            catch
            {
                return new AgentConfig();
            }
        }

        public void Save()
        {
            File.WriteAllText(ConfigPath, JsonConvert.SerializeObject(this, Formatting.Indented));
        }
    }

    public class DeviceConfig
    {
        public string Name { get; set; } = "";
        public string SerialNumber { get; set; } = "";
        public string IpAddress { get; set; } = "";
        public int Port { get; set; } = 4370;
        public string Location { get; set; } = "";
    }
}

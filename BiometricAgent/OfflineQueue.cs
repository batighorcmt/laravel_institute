using System;
using System.Collections.Generic;
using System.IO;
using Microsoft.Data.Sqlite;
using Newtonsoft.Json;

namespace BiometricAgent
{
    // ─────────────────────────────────────────────────────────────────────────
    // Local SQLite database to queue punches when internet is unavailable
    // ─────────────────────────────────────────────────────────────────────────
    public class OfflineQueue
    {
        private readonly string _dbPath;

        public OfflineQueue()
        {
            _dbPath = Path.Combine(AppDomain.CurrentDomain.BaseDirectory, "offline_queue.db");
            EnsureDatabase();
        }

        private void EnsureDatabase()
        {
            using var conn = OpenConnection();
            var cmd = conn.CreateCommand();
            cmd.CommandText = @"
                CREATE TABLE IF NOT EXISTS offline_queue (
                    id          INTEGER PRIMARY KEY AUTOINCREMENT,
                    school_id   INTEGER NOT NULL,
                    device_serial TEXT NOT NULL,
                    data        TEXT NOT NULL,
                    status      TEXT NOT NULL DEFAULT 'pending',
                    retry_count INTEGER NOT NULL DEFAULT 0,
                    created_at  TEXT NOT NULL
                );";
            cmd.ExecuteNonQuery();
        }

        private SqliteConnection OpenConnection()
        {
            var conn = new SqliteConnection($"Data Source={_dbPath}");
            conn.Open();
            return conn;
        }

        public void Enqueue(int schoolId, string deviceSerial, List<PunchRecord> punches)
        {
            using var conn = OpenConnection();
            var cmd = conn.CreateCommand();
            cmd.CommandText = @"
                INSERT INTO offline_queue (school_id, device_serial, data, status, retry_count, created_at)
                VALUES ($school, $serial, $data, 'pending', 0, $now)";
            cmd.Parameters.AddWithValue("$school", schoolId);
            cmd.Parameters.AddWithValue("$serial", deviceSerial);
            cmd.Parameters.AddWithValue("$data", JsonConvert.SerializeObject(punches));
            cmd.Parameters.AddWithValue("$now", DateTime.UtcNow.ToString("o"));
            cmd.ExecuteNonQuery();
        }

        public List<QueuedItem> GetPending(int limit = 50)
        {
            var items = new List<QueuedItem>();
            using var conn = OpenConnection();
            var cmd = conn.CreateCommand();
            cmd.CommandText = @"
                SELECT id, school_id, device_serial, data, retry_count
                FROM offline_queue
                WHERE status = 'pending' AND retry_count < 5
                ORDER BY id ASC
                LIMIT $limit";
            cmd.Parameters.AddWithValue("$limit", limit);

            using var reader = cmd.ExecuteReader();
            while (reader.Read())
            {
                items.Add(new QueuedItem
                {
                    Id = reader.GetInt64(0),
                    SchoolId = reader.GetInt32(1),
                    DeviceSerial = reader.GetString(2),
                    Data = JsonConvert.DeserializeObject<List<PunchRecord>>(reader.GetString(3)) ?? new(),
                    RetryCount = reader.GetInt32(4)
                });
            }
            return items;
        }

        public void MarkSuccess(long id)
        {
            using var conn = OpenConnection();
            var cmd = conn.CreateCommand();
            cmd.CommandText = "UPDATE offline_queue SET status='sent' WHERE id=$id";
            cmd.Parameters.AddWithValue("$id", id);
            cmd.ExecuteNonQuery();
        }

        public void IncrementRetry(long id)
        {
            using var conn = OpenConnection();
            var cmd = conn.CreateCommand();
            cmd.CommandText = "UPDATE offline_queue SET retry_count = retry_count + 1 WHERE id=$id";
            cmd.Parameters.AddWithValue("$id", id);
            cmd.ExecuteNonQuery();
        }
    }

    public class QueuedItem
    {
        public long Id { get; set; }
        public int SchoolId { get; set; }
        public string DeviceSerial { get; set; } = "";
        public List<PunchRecord> Data { get; set; } = new();
        public int RetryCount { get; set; }
    }

    public class PunchRecord
    {
        [JsonProperty("biometric_id")]
        public string BiometricId { get; set; } = "";

        [JsonProperty("punch_time")]
        public string PunchTime { get; set; } = "";

        [JsonProperty("punch_type")]
        public string? PunchType { get; set; }
    }

    public class BiometricTemplate
    {
        [JsonProperty("biometric_id")]
        public string BiometricId { get; set; } = "";

        [JsonProperty("finger_index")]
        public int FingerIndex { get; set; }

        [JsonProperty("template_data")]
        public string TemplateData { get; set; } = "";

        [JsonProperty("privilege")]
        public int Privilege { get; set; }

        [JsonProperty("name")]
        public string? Name { get; set; }
    }
}

using System;
using System.Collections.Generic;
using System.IO;
using System.Security.Cryptography;
using System.Text;
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
                );
                CREATE TABLE IF NOT EXISTS synced_punches (
                    device_serial TEXT NOT NULL,
                    biometric_id  TEXT NOT NULL,
                    punch_time    TEXT NOT NULL,
                    PRIMARY KEY (device_serial, biometric_id, punch_time)
                );
                CREATE TABLE IF NOT EXISTS synced_users (
                    device_serial TEXT NOT NULL,
                    biometric_id  TEXT NOT NULL,
                    user_hash     TEXT NOT NULL,
                    PRIMARY KEY (device_serial, biometric_id)
                );
                CREATE TABLE IF NOT EXISTS synced_templates (
                    device_serial TEXT NOT NULL,
                    biometric_id  TEXT NOT NULL,
                    finger_index  INTEGER NOT NULL,
                    template_hash TEXT NOT NULL,
                    PRIMARY KEY (device_serial, biometric_id, finger_index)
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

        public List<PunchRecord> FilterNewPunches(string deviceSerial, List<PunchRecord> allPunches)
        {
            var newPunches = new List<PunchRecord>();
            if (allPunches == null || allPunches.Count == 0) return newPunches;

            using var conn = OpenConnection();
            var cmd = conn.CreateCommand();
            cmd.CommandText = "SELECT 1 FROM synced_punches WHERE device_serial = $serial AND biometric_id = $bioId AND punch_time = $pTime";
            
            var serialParam = cmd.Parameters.Add("$serial", SqliteType.Text);
            var bioParam = cmd.Parameters.Add("$bioId", SqliteType.Text);
            var timeParam = cmd.Parameters.Add("$pTime", SqliteType.Text);

            serialParam.Value = deviceSerial;

            foreach (var punch in allPunches)
            {
                bioParam.Value = punch.BiometricId;
                timeParam.Value = punch.PunchTime;
                
                using var reader = cmd.ExecuteReader();
                if (!reader.Read())
                {
                    newPunches.Add(punch);
                }
            }

            return newPunches;
        }

        public void MigrateDeviceSerial(string oldSerial, string newSerial)
        {
            if (string.IsNullOrWhiteSpace(oldSerial) || string.IsNullOrWhiteSpace(newSerial)
                || string.Equals(oldSerial, newSerial, StringComparison.OrdinalIgnoreCase))
            {
                return;
            }

            using var conn = OpenConnection();
            using var transaction = conn.BeginTransaction();

            var selectCmd = conn.CreateCommand();
            selectCmd.Transaction = transaction;
            selectCmd.CommandText = "SELECT biometric_id, punch_time FROM synced_punches WHERE device_serial = $oldSerial";
            selectCmd.Parameters.AddWithValue("$oldSerial", oldSerial);

            var rows = new List<(string BiometricId, string PunchTime)>();
            using (var reader = selectCmd.ExecuteReader())
            {
                while (reader.Read())
                {
                    rows.Add((reader.GetString(0), reader.GetString(1)));
                }
            }

            var insertCmd = conn.CreateCommand();
            insertCmd.Transaction = transaction;
            insertCmd.CommandText = "INSERT OR IGNORE INTO synced_punches (device_serial, biometric_id, punch_time) VALUES ($newSerial, $bioId, $pTime)";
            insertCmd.Parameters.AddWithValue("$newSerial", newSerial);
            var bioParam = insertCmd.Parameters.Add("$bioId", SqliteType.Text);
            var timeParam = insertCmd.Parameters.Add("$pTime", SqliteType.Text);

            foreach (var row in rows)
            {
                bioParam.Value = row.BiometricId;
                timeParam.Value = row.PunchTime;
                insertCmd.ExecuteNonQuery();
            }

            var deleteCmd = conn.CreateCommand();
            deleteCmd.Transaction = transaction;
            deleteCmd.CommandText = "DELETE FROM synced_punches WHERE device_serial = $oldSerial";
            deleteCmd.Parameters.AddWithValue("$oldSerial", oldSerial);
            deleteCmd.ExecuteNonQuery();

            transaction.Commit();
        }

        public void MarkPunchesAsSynced(string deviceSerial, List<PunchRecord> syncedPunches)
        {
            if (syncedPunches == null || syncedPunches.Count == 0) return;

            using var conn = OpenConnection();
            using var transaction = conn.BeginTransaction();
            
            var cmd = conn.CreateCommand();
            cmd.Transaction = transaction;
            cmd.CommandText = "INSERT OR IGNORE INTO synced_punches (device_serial, biometric_id, punch_time) VALUES ($serial, $bioId, $pTime)";
            
            var serialParam = cmd.Parameters.Add("$serial", SqliteType.Text);
            var bioParam = cmd.Parameters.Add("$bioId", SqliteType.Text);
            var timeParam = cmd.Parameters.Add("$pTime", SqliteType.Text);

            serialParam.Value = deviceSerial;

            foreach (var punch in syncedPunches)
            {
                bioParam.Value = punch.BiometricId;
                timeParam.Value = punch.PunchTime;
                cmd.ExecuteNonQuery();
            }

            transaction.Commit();
        }

        public List<UserRecord> FilterUnsyncedUsers(string deviceSerial, List<UserRecord> users)
        {
            var unsynced = new List<UserRecord>();
            if (users == null || users.Count == 0) return unsynced;

            using var conn = OpenConnection();
            var cmd = conn.CreateCommand();
            cmd.CommandText = "SELECT user_hash FROM synced_users WHERE device_serial = $serial AND biometric_id = $bioId";

            var serialParam = cmd.Parameters.Add("$serial", SqliteType.Text);
            var bioParam = cmd.Parameters.Add("$bioId", SqliteType.Text);
            serialParam.Value = deviceSerial;

            foreach (var user in users)
            {
                bioParam.Value = user.BiometricId;
                string hash = ComputeHash($"{user.BiometricId}|{user.Name}|{user.Role}");

                using var reader = cmd.ExecuteReader();
                if (!reader.Read() || reader.GetString(0) != hash)
                {
                    unsynced.Add(user);
                }
            }

            return unsynced;
        }

        public void MarkUsersSynced(string deviceSerial, List<UserRecord> users)
        {
            if (users == null || users.Count == 0) return;

            using var conn = OpenConnection();
            using var transaction = conn.BeginTransaction();

            var cmd = conn.CreateCommand();
            cmd.Transaction = transaction;
            cmd.CommandText = "INSERT OR REPLACE INTO synced_users (device_serial, biometric_id, user_hash) VALUES ($serial, $bioId, $hash)";
            cmd.Parameters.AddWithValue("$serial", deviceSerial);
            var bioParam = cmd.Parameters.Add("$bioId", SqliteType.Text);
            var hashParam = cmd.Parameters.Add("$hash", SqliteType.Text);

            foreach (var user in users)
            {
                bioParam.Value = user.BiometricId;
                hashParam.Value = ComputeHash($"{user.BiometricId}|{user.Name}|{user.Role}");
                cmd.ExecuteNonQuery();
            }

            transaction.Commit();
        }

        public List<BiometricTemplate> FilterUnsyncedTemplates(string deviceSerial, List<BiometricTemplate> templates)
        {
            var unsynced = new List<BiometricTemplate>();
            if (templates == null || templates.Count == 0) return unsynced;

            using var conn = OpenConnection();
            var cmd = conn.CreateCommand();
            cmd.CommandText = "SELECT template_hash FROM synced_templates WHERE device_serial = $serial AND biometric_id = $bioId AND finger_index = $fingerIndex";

            var serialParam = cmd.Parameters.Add("$serial", SqliteType.Text);
            var bioParam = cmd.Parameters.Add("$bioId", SqliteType.Text);
            var fingerParam = cmd.Parameters.Add("$fingerIndex", SqliteType.Integer);
            serialParam.Value = deviceSerial;

            foreach (var template in templates)
            {
                bioParam.Value = template.BiometricId;
                fingerParam.Value = template.FingerIndex;
                string hash = ComputeHash($"{template.BiometricId}|{template.FingerIndex}|{template.TemplateData}");

                using var reader = cmd.ExecuteReader();
                if (!reader.Read() || reader.GetString(0) != hash)
                {
                    unsynced.Add(template);
                }
            }

            return unsynced;
        }

        public void MarkTemplatesSynced(string deviceSerial, List<BiometricTemplate> templates)
        {
            if (templates == null || templates.Count == 0) return;

            using var conn = OpenConnection();
            using var transaction = conn.BeginTransaction();

            var cmd = conn.CreateCommand();
            cmd.Transaction = transaction;
            cmd.CommandText = "INSERT OR REPLACE INTO synced_templates (device_serial, biometric_id, finger_index, template_hash) VALUES ($serial, $bioId, $fingerIndex, $hash)";
            cmd.Parameters.AddWithValue("$serial", deviceSerial);
            var bioParam = cmd.Parameters.Add("$bioId", SqliteType.Text);
            var fingerParam = cmd.Parameters.Add("$fingerIndex", SqliteType.Integer);
            var hashParam = cmd.Parameters.Add("$hash", SqliteType.Text);

            foreach (var template in templates)
            {
                bioParam.Value = template.BiometricId;
                fingerParam.Value = template.FingerIndex;
                hashParam.Value = ComputeHash($"{template.BiometricId}|{template.FingerIndex}|{template.TemplateData}");
                cmd.ExecuteNonQuery();
            }

            transaction.Commit();
        }

        private static string ComputeHash(string value)
        {
            using var sha = SHA256.Create();
            var bytes = Encoding.UTF8.GetBytes(value ?? string.Empty);
            var hashBytes = sha.ComputeHash(bytes);
            return Convert.ToHexString(hashBytes);
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

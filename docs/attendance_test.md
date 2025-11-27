# Attendance Feature Test Guide

Follow these steps to validate login, school_id resolution, and teacher self attendance end-to-end.

## 1. Acquire Token
```bash
# PowerShell (adjust BASE accordingly)
$BASE='http://your-domain/api/v1'
$USERNAME='teacher_username'
$PASSWORD='secret'
$DEVICE='flutter-app'

$login = Invoke-RestMethod -Method Post -Uri "$BASE/auth/login" -Body @{username=$USERNAME;password=$PASSWORD;device_name=$DEVICE}
$token = $login.token
$token
```

Expected JSON fragment:
```json
{
  "token": "...",
  "user": {"id": 5, "name": "Alice", "roles": [{"role":"teacher","school_id":3,"school_name":"Main"}]}
}
```
If `school_id` is null, run the artisan backfill command:
```bash
php artisan teacher:backfill-school-ids --school=3
```
Then logout/login again.

## 2. Verify /me Endpoint
```bash
$headers = @{ Authorization = "Bearer $token" }
Invoke-RestMethod -Method Get -Uri "$BASE/me" -Headers $headers
```
Confirm at least one role object has `role":"teacher"` and non-null `school_id`.

## 3. Fetch Settings
```bash
Invoke-RestMethod -Method Get -Uri "$BASE/teacher/attendance/settings" -Headers $headers
```
Expected keys: `check_in_start`, `check_in_end`, `late_threshold`, `school_id`.

## 4. Perform Check-In
Capture a temporary image file (simulate with existing file path if needed):
```bash
# If you have a test image
$img = "C:/temp/self.jpg"
$lat = 23.7808
$lng = 90.4070

Invoke-RestMethod -Method Post -Uri "$BASE/teacher/attendance" -Headers $headers -Form @{ lat=$lat; lng=$lng; school_id=3; photo=Get-Item $img }
```
Expected response contains `check_in_time` and `status` (present/late).

## 5. Perform Check-Out
```bash
Invoke-RestMethod -Method Post -Uri "$BASE/teacher/attendance/checkout" -Headers $headers -Form @{ lat=$lat; lng=$lng; school_id=3; photo=Get-Item $img }
```
Expected response contains `check_out_time`.

## 6. List Today Records
```bash
Invoke-RestMethod -Method Get -Uri "$BASE/teacher/attendance" -Headers $headers
```
Find today's record; verify both `check_in_time` & `check_out_time`.

## 7. Offline Queue Simulation (Optional)
Disable network, attempt check-in; expect local queue storage. Re-enable network and open the Flutter page to trigger flush, or manually replay queued entries.

## 8. Common Failure Checks
- 422 Missing school: ensure pivot rows have school_id.
- Late status logic: confirm `late_threshold` time > `check_in_time` for present; else late.
- Photo errors: verify image file path accessible to runtime.

## 9. Bengali Messages Validation
On successful operations Flutter should show:
- Check-in: `উপস্থিতি সফলভাবে নথিভুক্ত হয়েছে` or modal `চেক ইন সফল (অভিনন্দন)`
- Check-out: `প্রস্থান সফলভাবে নথিভুক্ত হয়েছে`

## 10. Cleanup
```bash
# Logout
Invoke-RestMethod -Method Post -Uri "$BASE/auth/logout" -Headers $headers
```

All tests passing indicates the TODO items (attendance flow) are complete.

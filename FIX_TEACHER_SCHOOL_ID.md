# Fix Missing school_id for Teachers

## Problem
Teachers on live server have NULL `school_id` in their `user_school_roles` records, causing attendance insertion to fail with SQLSTATE[23000].

## Quick Fix via Tinker

Run this on your live server:

```bash
php artisan tinker
```

Then execute:

```php
// Find all teacher roles with missing school_id
$teacherRole = \App\Models\Role::where('name', 'teacher')->first();
if ($teacherRole) {
    $missingSchool = \App\Models\UserSchoolRole::where('role_id', $teacherRole->id)
        ->whereNull('school_id')
        ->get();
    
    echo "Found {$missingSchool->count()} teacher role records with NULL school_id\n";
    
    foreach ($missingSchool as $pivot) {
        $user = $pivot->user;
        echo "User: {$user->name} (ID: {$user->id})\n";
        
        // Option 1: If there's only one school in the system, assign it
        $school = \App\Models\School::first();
        if ($school) {
            $pivot->school_id = $school->id;
            $pivot->save();
            echo "  → Assigned to school: {$school->name} (ID: {$school->id})\n";
        }
    }
    
    echo "\nFixed {$missingSchool->count()} records.\n";
}
```

## Alternative: SQL Direct Update

If you know the correct school_id (e.g., 73), run:

```sql
UPDATE user_school_roles 
SET school_id = 73 
WHERE role_id = (SELECT id FROM roles WHERE name = 'teacher') 
  AND school_id IS NULL;
```

## Verify After Fix

Check in tinker:

```php
$user = \App\Models\User::find(YOUR_TEACHER_USER_ID);
foreach ($user->activeSchoolRoles as $role) {
    echo "Role: {$role->role->name}, School: {$role->school_id} - {$role->school->name}\n";
}
```

The `/api/v1/me` endpoint should now return:

```json
{
  "id": X,
  "name": "Teacher Name",
  "roles": [
    {
      "role": "teacher",
      "school_id": 73,
      "school_name": "Your School Name"
    }
  ]
}
```

After this fix, the Flutter app will resolve `school_id` correctly and attendance submission will succeed.

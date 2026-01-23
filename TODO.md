# Document Memo Number Configuration

## Completed Tasks
- [x] Create migration to add memo_format column to document_settings table
- [x] Run migration
- [x] Update DocumentSetting model to include memo_format in fillable and casts
- [x] Update SettingsController to handle memo_format in validation and saving
- [x] Update settings view to include memo format checkboxes for keywords
- [x] Update DocumentMemoService to generate memo based on configured format

## Remaining Tasks
- [x] Add JavaScript to make the memo format sortable (drag and drop)
- [x] Implement custom_text as a configurable text field per setting
- [x] Implement class keyword by passing student to generate method
- [x] Update controllers to pass student if available for class keyword
- [x] Test the settings page and memo generation
- [x] Add validation for memo_format to ensure at least one keyword is selected

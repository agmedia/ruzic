<?php
// Heading
$_['heading_title'] = 'OPG Ružić – Anchor prices';

// Text
$_['text_extension'] = 'Extensions';
$_['text_list'] = 'Anchor price register';
$_['text_edit'] = 'Edit anchor price';
$_['text_filter'] = 'Filters';
$_['text_publications'] = 'Daily price lists';
$_['text_settings'] = 'Settings and automation';
$_['text_import'] = 'Bulk CSV import';
$_['text_import_errors'] = 'The import was not applied because of these errors:';
$_['text_no_results'] = 'No records found.';
$_['text_all_statuses'] = 'All statuses';
$_['text_status_confirmed'] = 'Confirmed';
$_['text_status_pending'] = 'Pending review';
$_['text_status_disabled'] = 'Disabled';
$_['text_system'] = 'System';
$_['text_missing_count'] = 'Active products without an anchor price: %s';
$_['text_success_edit'] = 'Success: The anchor price and audit trail have been updated.';
$_['text_success_sync'] = 'Success: %s missing anchor price(s) were created.';
$_['text_success_publish'] = 'Success: The CSV and XML price list was published: %s';
$_['text_success_repair_archive'] = 'Success: %s corrected publications were created; %s publications already have a correction. Original publications remain preserved.';
$_['text_archive_correction'] = 'Correction of publication #%s; originally published %s';
$_['text_success_settings'] = 'Success: The anchor price settings were saved.';
$_['text_success_import_dry_run'] = 'Validation succeeded: %s CSV rows are valid. No data was changed.';
$_['text_success_import'] = 'CSV import completed: %s created, %s updated.';
$_['text_reference_rule'] = 'The food-product baseline is 2 May 2025. Enter the actual price on that date, not today’s price. Products first sold later use their first selling price. Automatic records require review; all manual changes have an audit trail.';
$_['text_select_unit'] = 'Choose kg or l';
$_['text_measure_missing'] = 'Missing kg/l or quantity';
$_['text_cron_help'] = 'For technical integrations, call this URL daily before 08:00 Europe/Zagreb with the key in the X-Anchor-Price-Key header. Both CSV and XML are generated.';
$_['text_cron_cpanel_help'] = 'In the cPanel Cron Jobs GUI, call this complete URL (for example: curl -fsS "URL" >/dev/null). The key is secret; do not publish this URL.';
$_['text_audit'] = 'Audit trail';

// Columns
$_['column_product'] = 'Product';
$_['column_model'] = 'Model / SKU';
$_['column_net_price'] = 'Net package anchor price';
$_['column_gross_price'] = 'Gross package anchor price';
$_['column_package_quantity'] = 'Package quantity';
$_['column_unit_price'] = 'Current unit price';
$_['column_anchor_unit_price'] = 'Anchor unit price';
$_['column_reference_date'] = 'Reference date';
$_['column_status'] = 'Status';
$_['column_action'] = 'Action';
$_['column_location'] = 'Location';
$_['column_sequence'] = 'Sequence';
$_['column_filename'] = 'File';
$_['column_products'] = 'Products';
$_['column_published'] = 'Published';
$_['column_user'] = 'User';
$_['column_reason'] = 'Reason';
$_['column_before'] = 'Before';
$_['column_after'] = 'After';
$_['column_date_added'] = 'Date';

// Entries
$_['entry_filter_name'] = 'Product name';
$_['entry_filter_model'] = 'Model / SKU';
$_['entry_filter_status'] = 'Verification status';
$_['entry_date_from'] = 'Reference date from';
$_['entry_date_to'] = 'Reference date to';
$_['entry_price'] = 'Net package amount';
$_['entry_gross_price'] = 'Gross package amount';
$_['entry_unit'] = 'Unit of measure';
$_['entry_package_quantity'] = 'Quantity per package';
$_['entry_publication_type'] = 'Outlet / sales format';
$_['entry_publication_address'] = 'Outlet address';
$_['entry_publication_code'] = 'Outlet code';
$_['entry_reference_date'] = 'Reference date';
$_['entry_verification_status'] = 'Verification status';
$_['entry_reason'] = 'Reason for change';
$_['entry_default_unit'] = 'Default sales unit';
$_['entry_reference_date_setting'] = 'Baseline reference date';
$_['entry_cron_url'] = 'Daily cron URL';
$_['entry_cron_key'] = 'Cron key';
$_['entry_cron_cpanel'] = 'cPanel cron URL with key';
$_['entry_public_urls'] = 'Public latest price-list URLs';
$_['entry_import_file'] = 'CSV file';
$_['entry_dry_run'] = 'Validation only';

// Buttons
$_['button_filter'] = 'Filter';
$_['button_clear'] = 'Clear';
$_['button_sync'] = 'Create missing anchors';
$_['button_publish'] = 'Publish CSV and XML';
$_['button_repair_archive'] = 'Correct existing price lists';
$_['button_settings'] = 'Save settings';
$_['button_download'] = 'Download';
$_['button_import'] = 'Validate / import CSV';

// Help
$_['help_reason'] = 'Required. The reason is retained permanently in the audit trail.';
$_['help_gross_price'] = 'Whole-package anchor price including tax on its reference date (2 May 2025 for baseline food products). It can be corrected later with a mandatory audit reason; do not overwrite it with today’s price.';
$_['help_package_quantity'] = 'Net quantity in the selected unit, e.g. 5 for a 5 kg apple package or 3 for 3 l of juice. Unit price is package price divided by this quantity, not shipping weight.';
$_['help_publication_filename'] = 'File name: format_address_code_storagenumber_date_time.csv/xml. Defaults: webshop, outlet address and WEB. Format/code use letters, digits, hyphens or underscores; the address is converted to a safe file name.';
$_['help_repair_archive'] = 'Creates new CSV/XML corrections of existing price lists with the 2 May 2025 baseline, kg/l unit prices and compliant filenames. Prices and stock are taken from the original publication, not today’s data. Originals are unchanged; running again does not duplicate corrections.';
$_['help_default_unit'] = 'Unit and quantity are entered per product; the global unit is not used in price lists.';
$_['help_reference_date_setting'] = 'Applies to new baseline snapshots. Existing confirmed historical records are not overwritten.';
$_['help_import_file'] = 'Up to 5 MB / 10,000 rows. Required: one of product_id, model, sku or ean, plus the whole-package anchor_price (or gross_price) and reference_date. Optional: unit (kg/l), package_quantity, net_price, status and reason. Omitted unit/package_quantity retain existing values; confirmed rows require both. The price must match its historical date.';
$_['help_dry_run'] = 'Recommended: validate every row without writing. Untick only after validation passes.';

// Warnings and errors
$_['warning_publication_due'] = 'The daily price list has not been published by 08:00.';
$_['warning_product_added'] = 'The product was entered in this system on %s, after the baseline. This may not be its first sale date: use the baseline only if you can verify it was already sold then and its price on that date, explaining the change.';
$_['error_permission'] = 'Warning: You do not have permission to modify the Anchor prices module.';
$_['error_not_installed'] = 'The module tables do not exist. Install the module from Extensions first.';
$_['error_not_found'] = 'The requested anchor price was not found.';
$_['error_price'] = 'Enter a valid non-negative net amount.';
$_['error_gross_price'] = 'Enter a valid non-negative gross amount.';
$_['error_unit'] = 'Choose kg or l.';
$_['error_package_quantity'] = 'Enter a positive package quantity (at most 6 decimal places).';
$_['error_publication_settings'] = 'Enter format (1–24 letters/digits/hyphens/underscores), address (1–120 characters without HTML) and code (1–16 letters/digits/hyphens/underscores).';
$_['error_reference_date'] = 'Enter a valid date in YYYY-MM-DD format.';
$_['error_status'] = 'Select a valid verification status.';
$_['error_reason'] = 'The reason must contain between 3 and 255 characters.';
$_['error_default_unit'] = 'The default unit must contain between 1 and 16 characters.';
$_['error_reference_date_setting'] = 'The baseline reference date must be valid and must not be in the future.';
$_['error_import_upload'] = 'Choose a CSV file to import.';
$_['error_import_file'] = 'The file must be a .csv between 1 byte and 5 MB.';
$_['error_import_rows'] = 'The CSV was not applied. %s error(s) were found.';
$_['error_file_missing'] = 'The publication file is unavailable or has passed its 30-day retention period.';

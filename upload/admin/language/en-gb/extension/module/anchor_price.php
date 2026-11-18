<?php
// Heading
$_['heading_title'] = 'Voćarstvo Ružić d.o.o. – Base prices';

// Text
$_['text_extension'] = 'Extensions';
$_['text_list'] = 'Base price register';
$_['text_edit'] = 'Edit base price';
$_['text_filter'] = 'Filters';
$_['text_publications'] = 'Digital price lists';
$_['text_settings'] = 'Settings and automation';
$_['text_import'] = 'Bulk CSV import';
$_['text_import_errors'] = 'The import was not applied because of these errors:';
$_['text_no_results'] = 'No records found.';
$_['text_all_statuses'] = 'All statuses';
$_['text_status_confirmed'] = 'Confirmed';
$_['text_status_pending'] = 'Pending review';
$_['text_status_disabled'] = 'Disabled';
$_['text_system'] = 'System';
$_['text_missing_count'] = 'Active products without a base price: %s';
$_['text_success_edit'] = 'Success: The base price and audit trail have been updated.';
$_['text_success_sync'] = 'Success: %s missing base price(s) were created.';
$_['text_success_publish'] = 'Success: The CSV and XML price list was published: %s';
$_['text_success_repair_archive'] = 'Success: %s corrected publications were created; %s publications already have a correction. Original publications remain preserved.';
$_['text_archive_correction'] = 'Correction of publication #%s; originally published %s';
$_['text_success_settings'] = 'Success: The base price settings were saved.';
$_['text_success_import_dry_run'] = 'Validation succeeded: %s CSV rows are valid. No data was changed.';
$_['text_success_import'] = 'CSV import completed: %s created, %s updated.';
$_['text_reference_rule'] = 'For food, use 2 May 2025 or the first day on offer for products introduced later. If on sale at that time, verify the lowest price in the 30 days before the sale began. Confirm the amount and package size before activation.';
$_['text_select_unit'] = 'Choose kg or l';
$_['text_measure_missing'] = 'Missing kg/l or quantity';
$_['text_cron_help'] = 'Changes publish when saved. Schedule cron every minute to detect scheduled prices and retry failed publications.';
$_['text_cron_cpanel_help'] = 'In the cPanel Cron Jobs GUI, call this complete URL (for example: curl -fsS "URL" >/dev/null). The key is secret; do not publish this URL.';
$_['text_audit'] = 'Audit trail';

// Columns
$_['column_product'] = 'Product';
$_['column_model'] = 'Model / SKU';
$_['column_net_price'] = 'Net package base price';
$_['column_gross_price'] = 'Gross package base price';
$_['column_package_quantity'] = 'Package quantity';
$_['column_unit_price'] = 'Current unit price';
$_['column_anchor_unit_price'] = 'Base unit price';
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
$_['entry_reference_date'] = 'Reference date';
$_['entry_verification_status'] = 'Verification status';
$_['entry_reason'] = 'Reason for change';
$_['entry_default_unit'] = 'Default sales unit';
$_['entry_reference_date_setting'] = 'Baseline reference date';
$_['entry_cron_url'] = 'Cron URL';
$_['entry_cron_key'] = 'Cron key';
$_['entry_cron_cpanel'] = 'cPanel cron URL with key';
$_['entry_public_urls'] = 'Public latest price-list URLs';
$_['entry_import_file'] = 'CSV file';
$_['entry_dry_run'] = 'Validation only';

// Buttons
$_['button_filter'] = 'Filter';
$_['button_clear'] = 'Clear';
$_['button_sync'] = 'Create missing base prices';
$_['button_publish'] = 'Publish CSV and XML';
$_['button_repair_archive'] = 'Correct existing price lists';
$_['button_settings'] = 'Save settings';
$_['button_download'] = 'Download';
$_['button_import'] = 'Validate / import CSV';

// Help
$_['help_reason'] = 'Required. The reason is retained permanently in the audit trail.';
$_['help_gross_price'] = 'Whole-package base price including tax on its reference date (2 May 2025 for baseline food products). It can be corrected later with a mandatory audit reason; do not overwrite it with today’s price.';
$_['help_reference_date'] = 'You can correct the date of a confirmed record with a mandatory change reason. The food-product baseline is 2 May 2025; products introduced later use their first sale date. The price must match the selected historical date. The old and new dates remain in the audit trail; published price lists are not changed.';
$_['help_package_quantity'] = 'Net quantity in the selected unit, e.g. 5 for a 5 kg apple package or 3 for 3 l of juice. Unit price is package price divided by this quantity, not shipping weight.';
$_['help_publication_filename'] = 'OIB_ZA_SVA_PRODAJNA_MJESTA_ROBA_sequence_YYYYMMDD_HHMM.csv / .xml';
$_['help_repair_archive'] = 'Corrects measures in legacy lists using verified records and preserves historical prices.';
$_['help_default_unit'] = 'Unit and quantity are entered per product; the global unit is not used in price lists.';
$_['help_reference_date_setting'] = 'Applies to new baseline snapshots. Existing confirmed historical records are not overwritten.';
$_['help_import_file'] = 'Up to 5 MB / 10,000 rows. Required: one of product_id, model, sku or ean, plus the whole-package anchor_price (or gross_price) and reference_date. Optional: unit (kg/l), package_quantity, net_price, status and reason. Omitted unit/package_quantity retain existing values; confirmed rows require both. The price must match its historical date.';
$_['help_dry_run'] = 'Recommended: validate every row without writing. Untick only after validation passes.';

// Warnings and errors
$_['warning_product_added'] = 'The product was entered in this system on %s, after the baseline. This may not be its first sale date: use the baseline only if you can verify it was already sold then and its price on that date, explaining the change.';
$_['error_permission'] = 'Warning: You do not have permission to modify the Base prices module.';
$_['error_not_installed'] = 'The module tables do not exist. Install the module from Extensions first.';
$_['error_not_found'] = 'The requested base price was not found.';
$_['error_price'] = 'Enter a valid non-negative net amount.';
$_['error_gross_price'] = 'Enter a valid non-negative gross amount.';
$_['error_unit'] = 'Choose kg or l.';
$_['error_package_quantity'] = 'Enter a positive package quantity (at most 6 decimal places).';
$_['error_publication_settings'] = 'Enter the 11-digit merchant OIB.';
$_['error_reference_date'] = 'Enter a valid date in YYYY-MM-DD format.';
$_['error_reference_date_range'] = 'The reference date must be between %s and %s.';
$_['error_status'] = 'Select a valid verification status.';
$_['error_reason'] = 'The reason must contain between 3 and 255 characters.';
$_['error_default_unit'] = 'The default unit must contain between 1 and 16 characters.';
$_['error_reference_date_setting'] = 'The baseline reference date must be valid and must not be in the future.';
$_['error_import_upload'] = 'Choose a CSV file to import.';
$_['error_import_file'] = 'The file must be a .csv between 1 byte and 5 MB.';
$_['error_import_rows'] = 'The CSV was not applied. %s error(s) were found.';
$_['error_file_missing'] = 'The price list file is unavailable.';
$_['entry_publication_oib'] = 'Merchant OIB';
$_['text_last_cron'] = 'Last authenticated cron request';
$_['text_cron_unseen'] = 'No request recorded yet.';

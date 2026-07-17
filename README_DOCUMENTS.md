Documents feature

1. Run DB migration (as admin) to create the `documents` table:

   - Open in browser while signed in as admin:
     http://localhost/Orphanage/scripts/run_migrations.php

   Or run SQL directly (phpMyAdmin or mysql CLI):

   ```sql
   -- see sql/create_documents_table.sql
   ```

2. Upload documents as an adoptive user:
   - Visit: http://localhost/Orphanage/adoptive/upload_documents.php

3. View your documents:
   - Visit: http://localhost/Orphanage/adoptive/documents.php

4. Admin review:
   - Visit: http://localhost/Orphanage/admin/documents.php (admin only)

Notes:
- CSRF tokens are used for upload and admin actions.
- Files are stored in `uploads/documents/` and download links are provided.
- Dashboard counts auto-refresh every 15 seconds via `adoptive/get_dashboard_counts.php`.

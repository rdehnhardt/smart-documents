# Smart Documents

A secure document upload and management system built with Laravel 12, InertiaJS, and React.

## Features

- **Secure Document Upload**: Upload PDF, images (PNG, JPG, JPEG), Word documents (DOCX), Excel spreadsheets (XLSX), and text files
- **20MB File Limit**: Strict file size validation with MIME type verification
- **AI-Powered Analysis**: Automatic document analysis using Laravel AI for title, description, tags, summary, and sensitivity classification
- **Public Sharing**: Generate public URLs with secure tokens for sharing documents
- **QR Code Generation**: Automatic QR code generation for public documents
- **Visibility Control**: Toggle between private and public visibility (sensitive documents are always private)
- **Full-Text Search**: Search documents by title, description, original filename, and tags
- **Authenticated Access**: Fortify-powered authentication with email verification

## Requirements

- PHP 8.4+
- Node.js 18+
- SQLite (default) or MySQL/PostgreSQL
- Composer

## Installation

1. Clone the repository:
```bash
git clone <repository-url>
cd qr-documents
```

2. Install PHP dependencies:
```bash
composer install
```

3. Install JavaScript dependencies:
```bash
npm install
```

4. Copy the environment file and generate application key:
```bash
cp .env.example .env
php artisan key:generate
```

5. Create the SQLite database:
```bash
touch database/database.sqlite
```

6. Run migrations:
```bash
php artisan migrate
```

7. Create the storage link:
```bash
php artisan storage:link
```

8. Build frontend assets:
```bash
npm run build
```

## Development

Start the development server:
```bash
composer run dev
```

Or run services separately:
```bash
# Terminal 1: Laravel server
php artisan serve

# Terminal 2: Vite dev server
npm run dev

# Terminal 3: Queue worker (for AI analysis)
php artisan queue:work
```

## Configuration

### Storage

Documents are stored in a private disk configured in `config/filesystems.php`:
```php
'documents' => [
    'driver' => 'local',
    'root' => storage_path('app/private/documents'),
    'visibility' => 'private',
],
```

### AI Analysis

Configure your AI provider in `.env`:
```env
AI_PROVIDER=openai
OPENAI_API_KEY=your-api-key
```

The AI service analyzes uploaded documents to extract:
- Title suggestions
- Description
- Relevant tags
- Document summary
- Sensitivity classification (safe, sensitive, confidential)

Sensitive documents are automatically made private and cannot be made public.

## Usage

### Uploading Documents

1. Navigate to `/documents/create`
2. Select a file (PDF, PNG, JPG, JPEG, DOCX, XLSX, or TXT)
3. Optionally provide a title and description
4. Submit the form

The document will be uploaded and queued for AI analysis.

### Managing Documents

- **View**: Click on a document to see details, AI analysis, and download options
- **Edit**: Update title, description, and tags
- **Delete**: Remove the document permanently
- **Visibility**: Toggle between private and public (safe documents only)

### Public Sharing

Public documents can be accessed via:
- Direct link: `/p/{token}`
- QR code: `/p/{token}/qr`

Anyone with the link can view/download the document without authentication.

## API Routes

### Authenticated Routes (require login)

| Method | Route | Description |
|--------|-------|-------------|
| GET | `/documents` | List user's documents |
| GET | `/documents/create` | Upload form |
| POST | `/documents` | Store new document |
| GET | `/documents/{id}` | View document |
| GET | `/documents/{id}/edit` | Edit form |
| PUT | `/documents/{id}` | Update document |
| DELETE | `/documents/{id}` | Delete document |
| GET | `/documents/{id}/download` | Download document |
| POST | `/documents/{id}/make-public` | Make public |
| POST | `/documents/{id}/make-private` | Make private |
| POST | `/documents/{id}/reanalyze` | Re-run AI analysis |

### Public Routes

| Method | Route | Description |
|--------|-------|-------------|
| GET | `/p/{token}` | View/download public document |
| GET | `/p/{token}/qr` | Get QR code for document |

## Testing

Run the test suite:
```bash
php artisan test
```

Run with coverage:
```bash
php artisan test --coverage
```

Run specific tests:
```bash
php artisan test --filter=Document
```

## Architecture

### Key Components

- **Models**: `Document` - stores document metadata and AI analysis
- **Services**:
  - `DocumentStorageService` - handles file uploads, downloads, deletion
  - `DocumentAiService` - interfaces with AI for analysis
  - `QrCodeService` - generates QR codes for public documents
- **Jobs**: `AnalyzeDocumentJob` - queued job for async AI analysis
- **Policies**: `DocumentPolicy` - authorization rules for document access

### Database Schema

```
documents
├── id (primary key)
├── user_id (foreign key)
├── original_name (string)
├── mime_type (string)
├── size_bytes (integer)
├── storage_disk (string, default: 'documents')
├── storage_path (string)
├── visibility (enum: private, public)
├── public_token (string, nullable, unique)
├── title (string, nullable)
├── description (text, nullable)
├── tags (json, nullable)
├── ai_summary (text, nullable)
├── sensitivity (enum: null, safe, sensitive, confidential)
├── ai_analyzed (boolean)
├── created_at
└── updated_at
```

## Security

- Files are stored in a private directory outside the web root
- MIME type validation prevents malicious file uploads
- Sensitive documents detected by AI cannot be made public
- Public tokens are 64-character random strings
- Policy-based authorization ensures users can only access their own documents

## License

MIT
# smart-documents

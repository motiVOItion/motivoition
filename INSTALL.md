# 🚀 MotiVOItion Installation Guide

Welcome to the MotiVOItion Portfolio project! Follow these steps to get your portfolio up and running on your local machine or a live server.

## 📋 Prerequisites

Before you start, ensure you have the following installed:

1.  **PHP 7.4 or higher**: Required for the backend upload handler.
2.  **FFmpeg**: (Recommended) Used for generating video thumbnails automatically.
3.  **SQLite**: The project uses a lightweight SQLite database for metadata.

---

## 💻 Local Development Setup

If you are just checking the site locally on Windows/Mac/Linux:

1.  **Open a terminal** in the project folder.
2.  **Verify PHP installation**:
    ```bash
    php -v
    ```
    *If this command fails, you need to [install PHP](https://www.php.net/downloads) and add it to your System PATH.*
3.  **Start the local server**:
    ```bash
    php -S localhost:8000
    ```
4.  **Access the site**: Open your browser and go to `http://localhost:8000`.

---

## 🌐 Production Deployment

To host this on a web server (e.g., Bluehost, SiteGround, AWS):

1.  **Upload all files** to your server's public directory (e.g., `public_html` or `/var/www/html`).
2.  **Set Directory Permissions**:
    The following folders need write permissions (CHMOD 755 or 775):
    - `assets/uploads/`
    - `assets/uploads/thumbnails/`
    - `logs/`
    - `database/`

3.  **PHP Extensions**: Ensure `pdo_sqlite` and `curl` are enabled in your `php.ini`.

---

## 🔑 GitHub Integration

The project is already configured to sync uploads to GitHub. If you need to change the token or repository:

1.  Open `upload-handler.php`.
2.  Locate the `UploadConfig` class (lines 28-43).
3.  Update `GITHUB_TOKEN` and `GITHUB_REPO`.

---

## 🛠️ Troubleshooting

- **Upload Fails**: Check the `logs/upload.log` file for specific error messages.
- **No Thumbnails**: Ensure FFmpeg is installed and the path is accessible to PHP.
- **404 on Assets**: Ensure your server allows access to the `assets/` directory.

---

*Need help? Contact the developer via the `contact.html` page.*

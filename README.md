# 📂 PHP Indexer

<p align="center">
  <img src="https://img.shields.io/badge/PHP-7.4%2B-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP">
  <img src="https://img.shields.io/badge/Status-Self--Hosted-success?style=for-the-badge" alt="Status">
  <img src="https://img.shields.io/badge/UI-Modern_Glassmorphism-38B2AC?style=for-the-badge" alt="UI">
</p>

<p align="center">
  <strong>A high-performance directory listing script with built-in file management.</strong>
  <br />
  Upload, search, and securely delete files with a zero-dependency, single-file solution.
</p>

---

## 🚀 Key Functionalities

PHP Indexer is designed to be more than just a list—it's a mini file-manager for your server.

*   **⚡ Real-Time AJAX Search:** Instantly filter your files as you type. No page reloads, no server lag.
*   **📤 Drag & Drop Uploads:** Upload files directly to the current directory via the integrated file-upload zone.
*   **🗑️ Secure Deletion:** Features a "Hold to Delete" mechanism. Long-press the delete button for 10 seconds to confirm, followed by a secure password prompt.
*   **📱 Fully Responsive:** Adaptive CSS grid that flows perfectly from Ultrawide monitors to mobile devices.
*   **🎨 Glassmorphic UI:** A premium aesthetic using backdrop-filters, custom SVG iconography, and smooth staggering animations.

---

## 🛠️ Installation & Setup

### 1. Deployment
1.  Copy the `index.php` file into your desired directory.
2.  Open the file and set your desired **Administrator Password** at the top.

### 2. File System Permissions
The script requires write-access to your server to handle uploads and deletions. 

> [!IMPORTANT]
> If you encounter "Failed to move the uploaded file" errors, your web server user (usually `www-data`, `apache`, or `nginx`) needs ownership of the folder.

**Run these commands in your Linux terminal:**
```bash
# 1. Take ownership of the folder
sudo chown -R www-data:www-data /path/to/your/folder

# 2. Grant read/write permissions to the owner
sudo chmod -R 755 /path/to/your/folder
```

---

## 🖱️ Usage Guide

*   **Search:** Type in the top search bar to instantly see matching files.
*   **Upload:** Drag any file onto the interface to trigger the upload process.
*   **Delete:** 
    1. Click the "Delete" icon next to a file.
    2. A timer will start. **Hold the button down for 10 seconds.**
    3. Enter the administrator password to confirm the permanent removal.

---

## 🧬 Technical Implementation

| Feature | Implementation Logic |
| :--- | :--- |
| **Search** | Client-side JavaScript filtering on DOM elements (`display: none`). |
| **Uploads** | PHP `move_uploaded_file()` with server-side size/type validation. |
| **Deletion** | JS `mousedown/mouseup` timer logic + PHP `unlink()`. |
| **Icons** | Custom-generated inline SVG collection (no external requests). |
| **UI** | CSS Grid + Backdrop Blur + Staggered CSS Animation delays. |

---

## 🔧 Developer Customization

Everything is centralized for quick hacking:

*   **Hiding Files:** Edit the `continue` condition at the top of the PHP script:
    ```php
    if ($item === 'index.php' || $item === 'config.php') continue;
    ```
*   **Styling:** All styles are contained in the `<style>` block in `index.php`. You can easily adjust the `--accent` or `--bg-color` CSS variables to match your brand.

---

## ⚠️ Security Notes

1.  **Passwords:** Ensure you change the default password in the script immediately after installation.
2.  **Public Access:** If this folder is public, ensure your web server is configured to prevent unauthorized directory traversal.
3.  **Permissions:** Never use `chmod 777` on a public-facing server unless absolutely necessary for debugging.

---

## 📜 License

Distributed under the **MIT License**. Use it, tweak it, and master your server file management.

---

<p align="center">
  Built for developers who value speed and elegance. 🌌
</p>

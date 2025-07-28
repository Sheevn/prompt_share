# Simple PHP Prompt Library

A lightweight, database-free, file-based PHP application for creating, managing, and sharing a personal library of AI prompts. The application uses Markdown files for storing prompts, making it easy to manage and version control.

![Screenshot of the Prompt Sharer App](https://octoblogger.s3.ap-southeast-2.amazonaws.com/ps.jpg)

---

## Features

*   **Database-Free:** Works directly with `.md` files on your server. No database setup required.
*   **Secure Admin Panel:** A secret, PIN-protected admin page for managing your library.
*   **Full CRUD Functionality:** Create, Read, Update, and Delete prompts and categories.
*   **Dynamic Tagging:** Organize prompts with comma-separated tags and browse by tag.
*   **Powerful Search:** Full-text search of prompt titles and content.
*   **Modern UI:** A clean, responsive interface with a two-column admin dashboard and a dark/light mode toggle.
*   **User-Friendly:** Includes features like "Copy to Clipboard," social sharing, and a "Recently Added" section.

## Getting Started

### Prerequisites

*   A web server with PHP enabled (PHP 7.4+ recommended).
*   The `Parsedown.php` library for Markdown rendering.

### Installation

1.  **Download the Code:** Clone or download this repository to your web server.
    ```bash
    git clone https://github.com/YourUsername/your-repo-name.git
    ```
2.  **Install Dependencies:** Download `Parsedown.php` from [here](https://raw.githubusercontent.com/erusev/parsedown/master/Parsedown.php) and place it inside the `lib/` directory.
3.  **Set Permissions:** Ensure your web server has permission to write to the `prompts/` and `comments/` directories. You may need to set their permissions to `755` or `777` depending on your server configuration.
    ```bash
    chmod -R 755 prompts comments
    ```

## Configuration

Before using the application, you must configure two things for security:

1.  **Set Your Admin PIN:**
    *   Open the `super_secret_control_panel.php` file.
    *   Find the line `$admin_pin = 'CHANGE_THIS_TO_YOUR_PIN';`
    *   Change the value to a strong, secret PIN that you will remember.

2.  **Rename the Admin File (Highly Recommended):**
    *   For maximum security, rename the `super_secret_control_panel.php` file to something random and unguessable (e.g., `a9c3b1d8_manager.php`). This prevents attackers from finding your login page.

## Usage

*   **Access the Site:** Navigate to the main `index.php` in your browser.
*   **Access the Admin Panel:** Navigate to your newly renamed admin file (e.g., `your-site.com/a9c3b1d8_manager.php`). You will be prompted to enter your PIN.
*   **Manage Prompts:** Use the tabbed interface to add new prompts by pasting text or uploading `.md` files.
*   **Manage Categories:** Use the Category Management section to rename or delete entire categories.

## License

This project is licensed under the MIT License.

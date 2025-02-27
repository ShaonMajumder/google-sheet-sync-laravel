# Google Sheet Sync Laravel

A Laravel-based project to interact with Google Sheets API, allowing users to create, read, update, and append data to Google Sheets directly from a Laravel application.



## Project Details

- **Framework**: Laravel Framework 8.83.29
- **Purpose**: Synchronize data between your Laravel application and Google Sheets using Google Sheets API.

## Features

- Create new Google Spreadsheets.
- Add new sheets to existing spreadsheets.
- Insert data into specific sheets.
- Read data from Google Sheets.
- Append rows to Google Sheets.

## Prerequisites

1. **Google Cloud Project**:
   
   - **OAuth Access**: Authenticate users via OAuth to interact with Google Sheets.
   - Create a project in the Google Cloud Console.
   - Enable the **Google Sheets API** and **Google Drive API**.
   - Create OAuth 2.0 credentials and download the `credentials.json` file.

2. **Environment Setup**:

   - PHP >= 7.4
   - Composer
   - Laravel Framework 8.83.29

3. **Other Requirements**:

   - Access to a Redis server for token storage.

   1. - 

   ## Installation

   1. Clone the repository:

      ```
      git clone https://github.com/your-repo/google-sheet-sync-laravel.git
      cd google-sheet-sync-laravel
      ```

   2. Install dependencies:

      ```
      composer install
      ```

   3. Set up environment variables:

      ```
      cp .env.example .env
      ```

      Update the following values in `.env`:

      ```
      CREDENTIALS_FILE=/path/to/your/credentials.json
      SPREADSHEET_ID=your-default-spreadsheet-id
      ```

   4. Generate application key:

      ```
      php artisan key:generate
      ```

   5. Clear and cache configurations:

      ```
      php artisan config:clear
      php artisan cache:clear
      php artisan config:cache
      ```

   6. Set up token storage: Ensure you have a Redis server running and update `.env`:

      ```
      REDIS_HOST=127.0.0.1
      REDIS_PASSWORD=null
      REDIS_PORT=6379
      ```

   ## Usage

   ### Step 1: Authentication

   1. Visit the route to initiate the OAuth flow:

      ```
      http://localhost/oauth
      ```

   2. Authenticate with Google and obtain the authorization code.

   3. Paste the authorization code into the required field or endpoint (e.g., `/oauth/callback`).

   4. The application will save the token in Redis for future API requests.

   ### Step 2: Synchronization

   Use the following route to interact with Google Sheets:

   - **Sync Route**:

     ```
     http://localhost/sheet
     ```

     This route will trigger the `GoogleSheetSyncController` to sync data with Google Sheets.

   ## Key Classes

   ### GoogleSheetHelper

   This service class handles interactions with the Google Sheets API. It includes methods such as:

   - `createSpreadsheet($title, $data = null)`
   - `createSheet($sheetName)`
   - `insertData($sheetName, $data)`
   - `readSheet($sheetName)`
   - `appendRow($rowData, $sheetName)`

   ### Example Usage

   ```
   $googleSheetHelper = new GoogleSheetHelper();
   
   // Create a new spreadsheet
   $spreadsheetId = $googleSheetHelper->createSpreadsheet('Sample Spreadsheet');
   
   // Add a new sheet
   $sheetId = $googleSheetHelper->createSheet('Sample Sheet');
   
   // Insert data
   $data = [
       ['Name', 'Age', 'City'],
       ['Alice', '30', 'New York']
   ];
   $googleSheetHelper->insertData('Sample Sheet', $data);
   
   // Read data
   $data = $googleSheetHelper->readSheet('Sample Sheet');
   print_r($data);
   
   // Append a row
   $newRow = ['Bob', '25', 'Los Angeles'];
   $googleSheetHelper->appendRow($newRow, 'Sample Sheet');
   ```

   ## Development Samples
   ```json
   $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
                // Example : $accessToken
                // array:6 [▼
                //     "access_token" => "ya29.a0ARW5m74hIjmOX3v6xxtK_u6HTDEeqCreYEzF-yAmeXItvTa1F3-n3KCcGF1seh9kru9eGE3-GL3JDe4gd2Ns8F2hUOYiEjCN4cauvBsjTX9grYeVxgpJNKLL8LCh9j1Z6B5kg6zdxDs-XgRv5gAfTOcAg ▶"
                //     "expires_in" => 3599
                //     "refresh_token" => "1//0gnUMZXXKgU-KCgYIARAAGBASNwF-L9Irq-ZsM5gXDlXf2SbCvP_-6uqNebKkIeTmxIQzmO0C3MHyM3rIQuSPlS1oXNPp2mh_QJY"
                //     "scope" => "https://www.googleapis.com/auth/spreadsheets"
                //     "token_type" => "Bearer"
                //     "created" => 1737240385
                // ]
   ```

   ## Troubleshooting

   ### 1. **Redis Issues**

   If you encounter issues with Redis, follow these steps:

   - Install `php-redis`:

      ```bash
      sudo apt-get install php-redis
      ```

   - Ensure Redis is running and accessible by the application.

   ### 2. **Docker and Redis Connection**

   If you're using Docker, you may need to update the `REDIS_HOST` to the IP address of your Docker container. Follow these steps:

   1. Check the Docker network:

      ```bash
      ip addr show docker0
      ```

      Look for the IP address under `inet`, typically something like `172.17.0.1`.

   2. Update the `.env` file with the Docker IP:

      ```env
      REDIS_HOST=172.17.0.1
      ```

   ### 3. **OAuth Issues**

   - Ensure that the `credentials.json` file is properly configured and has the correct permissions.
   - Double-check the redirect URI in both the Google Cloud Console and your `.env` file.
   - If you get an expired token, the application will automatically refresh it as long as the refresh token is available.

   ## Common Issues

   1. **Redirect URI Mismatch**: Ensure the redirect URI matches the one set in Google Cloud Console.
   2. **Token Expiry**: The application automatically refreshes the token if it expires, as long as the refresh token is available.
   3. **Permission Errors**: Ensure the `credentials.json` file has the correct permissions and API scopes.

   ## Contributing

   Feel free to fork the repository and submit pull requests.

   ## License

   This project is licensed under the MIT License.

   ## Author

   Shaon Majumder
   smazoomder@gmail.com

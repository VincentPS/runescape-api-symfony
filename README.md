# RuneMetrics Re-Creation Project

[![main branch](https://github.com/VincentPS/runescape-api-symfony/actions/workflows/lintAndTests.yml/badge.svg)](https://github.com/VincentPS/runescape-api-symfony)
[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D_8.3-8892BF.svg?logo=php)](https://www.php.net/releases/8.3/en.php)
[![Static Badge](https://img.shields.io/badge/symfony-%3E%3D_7.2-green?logo=symfony)](https://symfony.com/releases/7.2)

Live version [https://rm-tracker.com](https://rm-tracker.com)

Welcome to the RuneMetrics Re-Creation project! This Symfony 7 application, built using PHP 8.3, aims to recreate the functionality of the RuneMetrics apps from Jagex. It leverages the public RuneScape API to gather player data and provides a user-friendly interface to view and analyze the data. This README will guide you through the setup, configuration, and usage of the project.

## Table of Contents

- Prerequisites
- Installation
- Configuration
- Usage
- API Wrapper
- Contributing
- License

## Prerequisites

Before getting started, ensure you have the following prerequisites installed:

- PHP 8.3
- Composer (Dependency Manager for PHP)
- Symfony CLI
- Git
- Docker
- npm
- PostgreSQL

## Installation

1. Clone the repository to your local machine:
```bash
git clone https://github.com/VincentPS/runescape-api-symfony.git
```

2. Navigate to the project directory:
```bash
cd runescape-api-symfony
```

3. Install project dependencies using Composer:
```bash
composer install
```

4. Run Docker
```bash
docker compose up
```

5. Set up NPM
```bash
npm install
npm run dev
```

## Configuration

1. Create a `.env.local` file in the project root and configure your database connection:
   DATABASE_URL=mysql://username:password@localhost:3306/database_name

2. Configure any other desired settings in the `.env.local` file.

3. Create the database schema:
   php bin/console doctrine:database:create
   php bin/console doctrine:migrations:migrate

4. The PHP modules are configured in the `php.ini` file, the following modules are required:
 ```
extension=curl
extension=ftp
extension=intl
extension=openssl
extension=pdo_pgsql
extension=pgsql
```

## Cetrificate for Curl (Guzzle)
This is only needed for local development, in production the server should have a valid certificate.  
Download the latest cacert.pem file from https://curl.se/docs/caextract.html and configure your php.ini file to use it:
 ```ini
 [curl]
 curl.cainfo = "{path to cacert.pem file}"
 [openssl]
 openssl.cafile = "{path to cacert.pem file}"
 ```

## Usage

1. Start the Symfony development server:
   symfony server:start

2. Access the application in your browser at `http://localhost:8000`.

3. Explore the various features provided by the application, such as viewing player stats, activities, and other RuneMetrics data.

4. If you want to update the data of a user through the CLI you can do so by using the following command:
   ```php bin/console rsapi:update <player-name>```.

5. A scheduled task can be set up to update the data of a user every minute. To do this, you can use the following command:
   ```php bin/console messenger:consume scheduler_update_player_data```, which user is updated can be changed in ```KnownPlayers::currentMain()```.

6. There is a way to validate the integrity of the data stored for a single user. To do this, you can use the following command:
   ```php bin/console db:verify-player-data-integrity <player-name>```.

## API Wrapper

This project includes an API wrapper for the RuneScape API to simplify data fetching. The wrapper can be found in the `src/Service/RSApiService.php` file. You can extend this wrapper to add more functionality or customize the API calls as needed.

## Contributing

We welcome contributions to the RuneMetrics Re-Creation project! If you'd like to contribute, please follow these steps:

1. Fork the repository.
2. Create a new branch for your feature or bug fix.
3. Make your changes and commit them with descriptive messages.
4. Push your changes to your fork.
5. Submit a pull request to the main repository.

## License

This project is licensed under the MIT License. Feel free to use and modify the code as per the terms of the license.

---

Thank you for choosing the RuneMetrics Re-Creation project! If you encounter any issues or have suggestions, please open an issue on the GitHub repository. Happy coding! 🚀


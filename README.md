# GitCodeGud 🚀
## Competition: 🏆
**boot.dev, devActivity, BountyHub**

- On **boot.dev** 📚, users can learn programming and earn XP in the process.
- On **devActivity** 🛠️, users can gain experience while contributing to a project and earn XP.
- On **BountyHub** 💰, users can fix bugs in other people’s repositories for money, but the platform lacks skill progression, personal development features, and gamification.

## Description: 📝
This application would assist programmers by allowing them to link their git repositories to the GitCodeGud platform. This way, they can work on their tasks as usual, but on the GitCodeGud platform, other programmers can fix bugs in other people’s projects in exchange for XP. Additional XP can also be earned for successful pull requests or even for discovering a bug (fixed amount).

## Theme: 🐞🎮
 Bug hunting, with a modern, more engaging look and feel.

## Technology stack: 🧑‍💻
- GitHub/~~GitLab~~ login using OAuth2 (Laravel Socialite) 🔑
- PostgreSQL database for storing data 🗄️
- Vue.js, Tailwind CSS – frontend 🎨
- Laravel – backend ⚙️

## Setup: ⚡
### Prerequisites: 📋
- PHP >= 8.1
- Composer
- Node.js >= 16
- npm
- PostgreSQL
- Git

### Installation: 🛠️
1. Clone the repository: 📥
    ```bash
   git clone https://github.com/Ag4Hz/GitCodeGud.git
   ```
2. Navigate to the project directory: 📂
    ```bash
   cd GitCodeGud
   ```
3. Install dependencies using Composer: 🎼
    ```bash
    composer install
    ```
4. Install frontend dependencies using npm: 📦
    ```bash
    npm install
    ```
5. Copy the example environment file and configure it: 📝
    ```bash
    cp .env.example .env
    ```
    #### Variables to set in the `.env` file: ⚙️
   - DB_CONNECTION=pgsql
   - DB_HOST=<your_database_host>
   - DB_PORT=<your_database_port>
   - DB_DATABASE=<your_database_name>
   - DB_USERNAME=<your_database_username>
   - DB_PASSWORD=<your_database_password>
   - GITHUB_CLIENT_ID=<your_github_client_id>
   - GITHUB_CLIENT_SECRET=<your_github_client_secret>
   - GITHUB_REDIRECT_URL=http://localhost:8000/auth/github/callback

### Setting up GitHub OAuth App: 🔐
1. Go to [GitHub Developer Settings] ⚙️
2. Click on "New OAuth App". ➕
3. Fill in the required fields: 📝
    - Application Name: GitCodeGud
    - Homepage URL: http://localhost:8000
    - Authorization Callback URL: http://localhost:8000/auth/github/callback
4. After creating the app, you will get the Client ID and Client Secret. Use these values in your `.env` file. 🔑

### Database setup: 🗄️
1. Create a PostgreSQL database for the application. 🏗️
2. Run the migrations to set up the database schema: 🏃
    ```bash
    php artisan migrate
    ```
3. (Optional) Seed the database with initial data: 🌱
    ```bash
    php artisan db:seed
    ```

### Running the application: ▶️
1. Start the Laravel development server: 🖥️
    ```bash
   composer run dev
    ```

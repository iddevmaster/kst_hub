# KST Hub
 
install project as :
- `composer install`
- create .env file
- copy .env.example to .env file
- config database connection in .env (such as `DB_HOST` , `DB_DATABASE` , `DB_USERNAME` , `DB_PASSWORD`)
- run `php artisan key:generate` to generate the app key.
- Create Database name `kst_hub_db` or other
- `php artisan migrate`
- `php artisan db:seed`

to start dev server is:
- `npm run dev`, if you got some issue, please run `npm i` befor;
- `php artisan serve`

Don't forget to update the .env file after pulling the project from Git.

after you install project,the default admin user is : 
- Username: `superadmin`
- Password: `iddrivesadmin`

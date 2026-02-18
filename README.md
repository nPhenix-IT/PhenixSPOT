# **Phenix Radius** #

    Created By Delario

### **Features**
- Multi User / Multi Tenancy (SaaS enabled)
- Sales Dashboard
- Router/NAS Management
- Profiles Management
- Voucher Generation with QR Code
- Voucher print
- Customizable Voucher
- Online voucher purchase
- and more ...

#### **Compatible with any NAS/Router that Support Radius**


## **Instruction** ##

## System Requirements
Node: v18.12.0 or above (LTS)
PHP: v8.2.0 or Above
Composer: v2.2 or Above


Clone this repository. Run the following command:
```
git clone https://github.com/nPhenix-IT/RADIUSSPOT.git
```

Move to the project directory:
```
cd RADIUSSPOT
```

install dependencies using Composer
```
composer install
```

Find .env.example file at root folder and copy it to .env by running below command Or also can manually copy it (if not having .env file):
```
cp .env.example .env
```

Run the following command to generate the key
```
php artisan key:generate
```

Install all node the dependencies:
```
yarn
```

Find .env.example file at root folder and copy it to .env by running below command Or also can manually copy it (if not having .env file):
```
cp .env.example .env
```
Run the following command to generate the key
```
php artisan key:generate
```

### **For Development Mode: Run this command**
```
php artisan serve && yarn dev
```
### **For Production Mode: Run these commands**
```
yarn build
```

### **To integrate with database migrations:**

You have to set your database credentials in .env file :
```
DB_CONNECTION = mysql
DB_HOST = 127.0.0.1
DB_PORT = 3306
DB_DATABASE = DATABASE_NAME
DB_USERNAME = DATABASE_USERNAME
DB_PASSWORD = DATABASE_PASSWORD
```

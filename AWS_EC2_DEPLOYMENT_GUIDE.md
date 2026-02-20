# AWS EC2 Deployment Guide for Notes App

## Prerequisites
- AWS Account
- SSH client (PuTTY for Windows or Terminal for Mac/Linux)
- Your project files ready to upload

---

## Step 1: Launch an EC2 Instance

### 1.1 Login to AWS Console
1. Go to https://aws.amazon.com/console/
2. Sign in to your AWS account

### 1.2 Launch EC2 Instance
1. Navigate to **EC2 Dashboard**
2. Click **"Launch Instance"**

### 1.3 Configure Instance
1. **Name**: notes-app-server
2. **AMI**: Amazon Linux 2023 or Ubuntu Server 22.04 LTS
3. **Instance Type**: t2.micro (Free Tier eligible)
4. **Key Pair**: 
   - Create new key pair
   - Name: notes-app-key
   - Type: RSA
   - Format: .pem (for Mac/Linux) or .ppk (for Windows/PuTTY)
   - **Download and save securely!**

### 1.4 Network Settings
1. Check **"Allow SSH traffic from"** → My IP
2. Check **"Allow HTTP traffic from the internet"**
3. Check **"Allow HTTPS traffic from the internet"**

### 1.5 Storage
- 8 GB gp3 (default is fine for this project)

### 1.6 Launch
- Click **"Launch Instance"**
- Wait for instance to be in "Running" state

---

## Step 2: Configure Security Group

### 2.1 Open Required Ports
1. Go to EC2 → Security Groups
2. Select your instance's security group
3. Edit Inbound Rules:

| Type  | Protocol | Port Range | Source    |
|-------|----------|------------|-----------|
| SSH   | TCP      | 22         | My IP     |
| HTTP  | TCP      | 80         | 0.0.0.0/0 |
| HTTPS | TCP      | 443        | 0.0.0.0/0 |
| MySQL | TCP      | 3306       | My IP     |

---

## Step 3: Connect to EC2 Instance

### 3.1 Get Public IP
- Copy the **Public IPv4 address** from EC2 console

### 3.2 Connect via SSH

**For Windows (using PuTTY):**
1. Open PuTTY
2. Host Name: `ec2-user@YOUR_PUBLIC_IP` (Amazon Linux) or `ubuntu@YOUR_PUBLIC_IP` (Ubuntu)
3. Connection → SSH → Auth → Credentials → Browse for your .ppk file
4. Click Open

**For Mac/Linux:**
```bash
chmod 400 notes-app-key.pem
ssh -i "notes-app-key.pem" ec2-user@YOUR_PUBLIC_IP
```

---

## Step 4: Install LAMP Stack

### For Amazon Linux 2023:

```bash
# Update system
sudo dnf update -y

# Install Apache
sudo dnf install -y httpd
sudo systemctl start httpd
sudo systemctl enable httpd

# Install PHP 8.x and extensions
sudo dnf install -y php php-mysqlnd php-fpm php-json php-mbstring php-xml php-opcache

# Restart Apache to load PHP
sudo systemctl restart httpd

# Install MariaDB (MySQL compatible)
sudo dnf install -y mariadb105-server
sudo systemctl start mariadb
sudo systemctl enable mariadb

# Secure MySQL installation
sudo mysql_secure_installation
# Answer: Set root password, remove anonymous users, disallow remote root login, remove test database
```

### For Ubuntu 22.04:

```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install Apache
sudo apt install -y apache2
sudo systemctl start apache2
sudo systemctl enable apache2

# Install PHP and extensions
sudo apt install -y php libapache2-mod-php php-mysql php-json php-mbstring php-xml php-opcache

# Restart Apache
sudo systemctl restart apache2

# Install MySQL
sudo apt install -y mysql-server
sudo systemctl start mysql
sudo systemctl enable mysql

# Secure MySQL
sudo mysql_secure_installation
```

---

## Step 5: Configure MySQL Database

```bash
# Login to MySQL
sudo mysql -u root -p

# In MySQL prompt, run:
CREATE DATABASE notes_app;
CREATE USER 'notes_user'@'localhost' IDENTIFIED BY 'YourSecurePassword123!';
GRANT ALL PRIVILEGES ON notes_app.* TO 'notes_user'@'localhost';
FLUSH PRIVILEGES;

# Create tables
USE notes_app;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE notes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT,
    is_deleted TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_is_deleted (is_deleted)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

EXIT;
```

---

## Step 6: Upload Project Files

### Option A: Using SCP (from your local machine)

**Windows (using WinSCP):**
1. Download WinSCP
2. Connect with your .ppk key
3. Drag and drop files to `/var/www/html/`

**Mac/Linux:**
```bash
# From your local machine
scp -i "notes-app-key.pem" -r ./notes-app/* ec2-user@YOUR_PUBLIC_IP:/tmp/notes-app/

# Then on EC2, move files
sudo cp -r /tmp/notes-app/* /var/www/html/
```

### Option B: Using Git

```bash
# On EC2 instance
sudo dnf install -y git   # Amazon Linux
# OR
sudo apt install -y git   # Ubuntu

cd /var/www/html
sudo git clone YOUR_REPO_URL .
```

### Option C: Manual Upload via SFTP
Use FileZilla or similar SFTP client with your key file.

---

## Step 7: Configure Project

### 7.1 Update Database Configuration
```bash
sudo nano /var/www/html/config/db.php
```

Update with your credentials:
```php
<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'notes_user');
define('DB_PASS', 'YourSecurePassword123!');
define('DB_NAME', 'notes_app');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
?>
```

### 7.2 Set File Permissions
```bash
# Set ownership to Apache
sudo chown -R apache:apache /var/www/html/    # Amazon Linux
# OR
sudo chown -R www-data:www-data /var/www/html/   # Ubuntu

# Set directory permissions
sudo chmod -R 755 /var/www/html/
```

---

## Step 8: Configure Apache

### 8.1 Create Virtual Host (Optional but recommended)

```bash
sudo nano /etc/httpd/conf.d/notes-app.conf    # Amazon Linux
# OR
sudo nano /etc/apache2/sites-available/notes-app.conf   # Ubuntu
```

Add:
```apache
<VirtualHost *:80>
    ServerName YOUR_PUBLIC_IP
    DocumentRoot /var/www/html
    
    <Directory /var/www/html>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog /var/log/httpd/notes-app-error.log
    CustomLog /var/log/httpd/notes-app-access.log combined
</VirtualHost>
```

### 8.2 Enable mod_rewrite (for future use)
```bash
# Amazon Linux
sudo nano /etc/httpd/conf/httpd.conf
# Change: AllowOverride None → AllowOverride All

# Ubuntu
sudo a2enmod rewrite
```

### 8.3 Restart Apache
```bash
sudo systemctl restart httpd    # Amazon Linux
# OR
sudo systemctl restart apache2   # Ubuntu
```

---

## Step 9: Test Your Application

1. Open browser
2. Go to: `http://YOUR_PUBLIC_IP`
3. You should see the Notes App login page!

---

## Step 10: Set Up Domain Name (Optional)

### 10.1 Register a Domain
- Use AWS Route 53, GoDaddy, Namecheap, etc.

### 10.2 Create Elastic IP
1. EC2 → Elastic IPs → Allocate Elastic IP
2. Associate with your instance

### 10.3 Configure DNS
- Add A record pointing to your Elastic IP

---

## Step 11: Enable HTTPS with SSL (Recommended)

```bash
# Install Certbot
sudo dnf install -y certbot python3-certbot-apache   # Amazon Linux
# OR
sudo apt install -y certbot python3-certbot-apache   # Ubuntu

# Get SSL certificate (replace with your domain)
sudo certbot --apache -d yourdomain.com

# Auto-renewal (add to crontab)
sudo crontab -e
# Add: 0 0 * * * /usr/bin/certbot renew --quiet
```

---

## Troubleshooting

### Check Apache Status
```bash
sudo systemctl status httpd
sudo tail -f /var/log/httpd/error_log
```

### Check PHP Info
```bash
php -v
echo "<?php phpinfo(); ?>" | sudo tee /var/www/html/info.php
# Visit: http://YOUR_IP/info.php
# Delete after testing: sudo rm /var/www/html/info.php
```

### Check MySQL Connection
```bash
mysql -u notes_user -p -e "SHOW DATABASES;"
```

### SELinux Issues (Amazon Linux)
```bash
sudo setsebool -P httpd_can_network_connect_db 1
```

---

## Security Best Practices

1. **Keep system updated**: `sudo dnf update -y` or `sudo apt update && sudo apt upgrade -y`
2. **Use strong passwords** for MySQL users
3. **Enable firewall**: Make sure security groups are properly configured
4. **Regular backups**: Set up automated MySQL backups
5. **Monitor logs**: Check Apache and MySQL logs regularly
6. **Use HTTPS**: Always use SSL in production

---

## Cost Estimation (AWS Free Tier)

| Service | Free Tier | After Free Tier |
|---------|-----------|-----------------|
| EC2 t2.micro | 750 hrs/month for 12 months | ~$8.50/month |
| EBS Storage | 30 GB | ~$0.08/GB/month |
| Data Transfer | 1 GB out | ~$0.09/GB |

---

## Quick Commands Reference

```bash
# Restart services
sudo systemctl restart httpd mariadb   # Amazon Linux
sudo systemctl restart apache2 mysql   # Ubuntu

# View logs
sudo tail -f /var/log/httpd/error_log
sudo tail -f /var/log/httpd/access_log

# Backup database
mysqldump -u notes_user -p notes_app > backup.sql

# Restore database
mysql -u notes_user -p notes_app < backup.sql
```

---

Your Notes App should now be live on AWS EC2! 🎉

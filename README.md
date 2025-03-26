# IPL_Management
A PHP & SQL-powered web application for managing the Indian Premier League (IPL) database. This system allows users to perform CRUD (Create, Read, Update, Delete) operations on teams, players, matches, and statistics efficiently.

✨ Features:
✅ Add, edit, and delete teams & players
✅ Manage teams & results
✅ Track player statistics and performance
✅ User-friendly dashboard for easy data management
✅ Secure database operations using SQL

💻 Built with PHP, SQL, HTML, CSS, JavaScript

🚀 Perfect for IPL enthusiasts & database management projects!

Steps to Run the IPL Management System on Your Device  

1. Install XAMPP  
   - Download and install [XAMPP](https://www.apachefriends.org/).  

2. Clone or Download the Project  
   - Open a terminal or command prompt and run:  
     ```
     git clone <your-github-repo-url>
     ```
   - Alternatively, download the ZIP file and extract it into `C:\xampp\htdocs\`.  

3. Start Apache & MySQL  
   - Open XAMPP Control Panel and start Apache and MySQL.  

4. Import the Database  
   - Open a browser and go to phpMyAdmin:  
     ```
     http://localhost/phpmyadmin/
     ```  
   - Create a new database (`ipl_management`).  
   - Import the provided `.sql` file into this database.  

5. Configure Database Connection  
   - Open the project folder and locate the database configuration file (e.g., `config.php` or `db_connect.php`).  
   - Update the database credentials if needed:  
     ```php
     $servername = "localhost";
     $username = "root";
     $password = "";
     $dbname = "ipl_management";
     ```  

6. Run the Application  
   - Open a browser and go to:  
     ```
     http://localhost/your_project_folder/
     ```  

Your IPL Management System should now be running!

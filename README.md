#Vortex
Vortex is a simple data entry web service written in PHP designed to log Minecraft player data to an SQL database.

It is currently designed to store a player's x, y and z coordinates, the dimension they are in, their UUID, session token and the hostname of the server they are currently connected to.

##Setup
Create a mysql database and import the schema
>$ mysqladmin -uroot -p create vortex

>$ mysql -uroot -p vortex < create_schema.sql

Open up vortex.php and edit the variables at the top of the file to match the connection details of your SQL instance.

Then drop vortex.php in your webroot and fire away POST requests as defined inside the file to your web server.

##flat2sql
When originally writing and debugging this program I'd log the data to a text file. vortex2sql is a simple program that can import the data from that file into the sql database. Simply edit a few variables at the top of the file and run it.

>$ php fla2sql.php
ALTER USER 'diagpro'@'%' IDENTIFIED WITH mysql_native_password BY 'diagpro_secure_2024';
ALTER USER 'root'@'%' IDENTIFIED WITH mysql_native_password BY 'tua_password_root_sicura';
ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY 'tua_password_root_sicura';
SET GLOBAL host_cache_size = 0;
FLUSH PRIVILEGES;

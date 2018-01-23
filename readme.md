# Model Console

## update 2017-12-31
    add default value to model if the field desc have 'DEFULT'
    
## step 0
    mv bin/ dir to project document_root
    orm demo in the bin/ dir mv to your path
    
## create database    
this command will create a database for your config
```php
    php bin/console.php orm:database:create app //config project name(in relations option)
```

## make model
this command will make the app's models to  dir /app/src/Finder and finders to  dir /app/src/Model
```php
    php bin/console.php orm:model:make app
```

## table update
this command will update table by config file
```php
    php bin/console.php orm:table:update
```
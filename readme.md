# Model Console

## update 2017-12-31
    add default value to model if the field desc have 'DEFULT'
    
## step 0
    mv bin/ dir to project document_root
    orm demo in the bin/ dir mv to your path
    
## create database
```angular2html
    php bin/console.php orm:database:create
```

## generate model
```angular2html
    php bin/console.php orm:model:make
```

## table update
```angular2html
    php bin/console.php orm:table:update
```
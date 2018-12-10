# oxProbs

oxProbs analyzes the data of an OXID eShop installation in order to find problems.
![no stock report](/docs/img/nostock.jpg)

With oxProbs you can find by default

- Product items 
  - without a stock
  - without a price (purchase and sales)
  - without a category
  - with deactivated parents (orphaned variants)
  - with no or wrong EAN
 
- Grouping
  - categories with deactivated parents
  - invalid actions
  - invalid manufacturers and deliverers

- Shipping
  - types and costs
  - countries and payments

- Users
  - Double registered users 

- Pictures
  - Inherited pictures 

and many other maybe unwanted and needless data.

An integrated module loader allows you to create and add our own reports without changing the code of the module itself.

For detailed information, please read the [Wiki](https://github.com/job963/oxProbs/wiki) (English / Deutsch).



### Install via Composer (Path repository)

- Create directory in root `/privateSrc/Job963/HealthCheck`
- Download module and copy code into created folder
- Add path repository to composer.json (Code after this list)
- Open terminal in root and enter `composer req job963/oxprobs`

    "repositories": [
        {
            "type": "path",
            "url": "privateSrc/Job963/HealthCheck"
        }
    ]

### Install via Composer (Git repository / NOT TESTET!)

- Add git repository to composer.json (Code after this list)
- Open terminal in root and enter `composer req job963/oxprobs`

    "repositories": [
        {
            "type":"package",
            "package": {
            "name": "job963/oxprobs",
            "version":"oxid6",
            "source": {
                "url": "https://github.com/job963/oxProbs",
                "type": "git",
                "reference":"oxid-6"
                }
            }
        }
    ]

### Install via Composer (Packagist.org)

- Package is not registrated via Packagist atm
- We work on it :)

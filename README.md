# M2 Advanced Reporting Troubleshooter
It is Magento module for help troubleshoot problems with Advanced Reporting 
## Contents


- [Installation](#installation)
- [Usage](#usage)
- [Uninstall](#uninstall)


## Installation

- Download the module:

  
  `composer require magentosupport/art:dev-master`

    In case of problems with composer installation feel free to install the module commonly by creating Magentosupport folder in app/code folder and clone this repo inside 
  

- Enable the module:
  
`php bin/magento module:enable MagentoSupport_ART` 

## Usage

Run command :

`php bin/magento analytics:troubleshoot`

##Uninstall

Disable module:

`php bin/magento module:disable MagentoSupport_ART`

Remove module:

`composer remove magentosupport/art`


# M2 Advanced Reporting Troubleshooter
It is Magento module for help troubleshoot problems with Advanced Reporting 
## Contents


- [Installation](#installation)
- [Usage](#usage)
- [Uninstall](#uninstall)


## Installation

- Download the module:

  
  `composer require roliinyk90/ART`
  

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
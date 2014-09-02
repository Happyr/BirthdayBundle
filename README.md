Happyr Birthday Bundle
======================

Get a nice looking form for birthdays. Enter the year manually and then drop down for.


## Install

``` yml
// app/config/config.yml
twig:
    form:
      resources:
        - 'HappyrBirthdayBundle:Form:fields.html.twig'

```

## Usage

``` php

//WhateverFormType.php

public function buildForm(FormBuilderInterface $builder, array $options)
{
   $builder->add('birthday', 'happyr_birthday')
}
```
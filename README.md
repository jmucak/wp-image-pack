# wp-image-pack

## Table of contents

1. [Project info](#project-info)
2. [Requirements](#requirements)
3. [Installation](#installation)
4. [Features](#features)
5. [Usage](#usage)

### Project info

WordPress image pack for custom theme and plugins development

- repository: `https://github.com/jmucak/wp-image-pack`

### Requirements

- PHP > 8.1
- composer v2

### Installation

#### Template setup

- Run `composer require jmucak/wp-image-pack` inside your custom theme or plugin folder

### Features

- creating images on demand
- de registering non-core image sizes

### Usage

- inside your theme/plugin call ImageProvider to register custom image sizes or de register non-core image sizes

#### Register image Sizes

```
new ImageProvider(array(
    'image_sizes' => array(
        'image_200'  => array( 200, 0, false ), // width, height, crop
        'image_800'  => array( 800, 0, true ),
        'image_1000' => array( 1000, 0, true ),
    ),
));
```

#### De register non-core image sizes

```
new ImageProvider(array(
    'deregister_image_sizes' => array('1536x1536', '2048x2048'),
));
```

- You can use ImageService class to get image by custom size name or by custom size

#### Get image by size name

```
$image_url = ImageService::get_instance()->get_image_url(1, 'image_800');
```

- you can also use registered image sizes

```
$image_url = ImageService::get_instance()->get_image_url(1, 'thumbnail');
$image_url = ImageService::get_instance()->get_image_url(1, 'medium');
```

#### Get image by custom size name

```
$image_url = ImageService::get_instance()->get_image_url( 1, array( 500, 0, true ) );
```

#### Get alt value

- This will get alt value from meta, but if nothing is entered in admin, it will show attachment's title

```
$image_alt = ImageService::get_instance()->get_image_alt(1);
```
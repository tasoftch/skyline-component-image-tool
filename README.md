# Skyline Image Tool Component
This package ships with an preconfigured API controller and a JS bundle to interact with images in your application.

### Installation
```bin
$ composer require skyline/component-image-tool
```

### Usage (Section PHP)
You need to create a subclass of the image tool api controller and route URIs to it.  
You need to declare those URIs to the JS bundle.

```php
<?php
use Skyline\ImageTool\Controller\AbstractImageToolAPIController;

class MyAPIController extends AbstractImageToolAPIController {
    /**
     * @route literal /api/v1/image-tool/fetch
     */
    public function fetchAction() {
        parent::fetchQueryAction( $_POST );
    }
    
    /**
     * @route literal /api/v1/image-tool/change
     * @role SKYLINE.ADMIN
     * @role SKYLINE.IMAGES.CHANGE
     */
    public function changeAction() {
        parent::changeQueryAction( $_POST );
    }
    
    /**
     * @route literal /api/v1/image-tool/put
     * @role SKYLINE.ADMIN
     * @role SKYLINE.IMAGES.UPLOAD
     */
    public function putAction() {
        parent::putQueryAction( $_POST, $_FILES );
    }
}
```

### Usage (JS)
In Javascript you have a bunch of available classes to access and modify the persisted images in your application.

Then you can send queries by api calls to the controller.
```js
const query = new Skyline.FetchQuery({
    reference: 'my-article',
    select:Skyline.FetchQuery.SELECT_NECESSARY
});

query.run( new Skyline.QueryTarget() );

```

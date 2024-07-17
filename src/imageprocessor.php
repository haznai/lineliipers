<?php

// Assume all necessary libraries are already installed via Composer
require "../vendor/autoload.php";

use Jcupitt\Vips;

if ($_SERVER["REQUEST_METHOD"] != "POST") {
    // Replace this with an appropriate error image or message
    die("This script only handles POST requests.");
}

if (!isset($_FILES["image"]) || $_FILES["image"]["error"] != UPLOAD_ERR_OK) {
    die("File upload error or no file uploaded.");
}

// File type validation
$allowedTypes = ["image/jpeg", "image/png", "image/gif"];
$fileType = $_FILES["image"]["type"];
if (!in_array($fileType, $allowedTypes)) {
    die("Invalid file type.");
}

// ======== Image Processing Begins Here ========

// Initialize Parameters
$stripe_thickness = 4;
$num_of_stripes = 20;
$angle = (67 * M_PI) / 180; // Convert to radians

// Load the uploaded image
$input_image = Vips\Image::newFromFile($_FILES["image"]["tmp_name"], ["access" => "random"]);

// preprocess image curves
function preprocess_image(Vips\Image $image)
{
    // there are lots of other params, see the docs
    $tone_operation = Vips\Image::tonelut([
        "Ps" => 0.3,
        "Pm" => 0.5,
        "Ph" => 0.8,
        "S" => -15,
        "M" => -30,
        "H" => +10,
    ]);

    $lab = $image->colourspace("labs");
    $lab[0] = $lab[0]->maplut($tone_operation);
    $output_image = $lab->cast("short")->colourspace("b-w");

    $image->writeToFile("testing_results/function_input_image.jpg");
    $output_image->writeToFile("testing_results/tonelut.jpg");

    return $output_image;
}

$input_image = preprocess_image($input_image);

/*** generation of stripe ***/

// 4. create stripe with double the height of the image
// get longest side of image and multiply it times two for the stripe width
$longest_size_of_image = 0;
if ($input_image->height > $input_image->width) {
    $longest_size_of_image = $input_image->height;
} else {
    $longest_size_of_image = $input_image->width;
}

$stripe = Vips\Image::black($longest_size_of_image * 2, $stripe_thickness);
// turn stripe white
$stripe = $stripe->add(255);

# make a vertical gradient from the start to end
# size it to match $image
function gradient($image, $start, $end): Vips\Image
{
    # a two-band image the size of $image whose pixel values are their
    $xyz = Vips\Image::xyz($image->width, $image->height);

    # the distance image: 0 - 1 for the start to the end of the gradient
    $d = $xyz[1]->divide($xyz->height);

    # and use it to fade the quads ... we need to tag the result as an RGB
    # image
    return $d
        ->multiply($end)
        ->add(
            $d
                ->multiply(-1)
                ->add(1)
                ->multiply($start)
        )
        ->copy(["interpretation" => "srgb"]);
}

// apply gradient on stripe
$grad = gradient($stripe, [0, 0, 0, 0], [0, 0, 0, 255]);
$stripe = $stripe->composite($grad, "over");

// mirror stripe vertically and add it under the original stripe
$mirrored_stripe = $stripe->flip("vertical");
$stripe = $stripe->join($mirrored_stripe, "vertical");

// add multiple stripes
$num_of_stripes = 10;
// @todo: adjust for space between stripes
// @todo: loop is currently recursive lmao
for ($i = 0; $i < $num_of_stripes; $i++) {
    $stripe = $stripe->join($stripe, "vertical");
}

// turn image and stripe to b/w colorspace to see if it changes something
$input_image = $input_image->colourspace("b-w");
$stripe = $stripe->colourspace("b-w");

// rotate stripe with affine operation
$stripe = $stripe->affine([
    cos($angle),
    sin($angle),
    -sin($angle),
    cos($angle),
]);

// recenter stripe
$stripe = $stripe->crop(
    $stripe->width / 2 - $input_image->width / 2,
    $stripe->height / 2 - $input_image->height / 2,
    $input_image->width,
    $input_image->height
);

// turn stripe to same size as $input_image
$stripe = $stripe->crop(0, 0, $input_image->width, $input_image->height);

$temp_image = $input_image->composite($stripe, "colour-dodge");

// ======== Image Processing Ends Here ========


// todo: maybe delete this header, is it needed?
header("Content-Type: image/jpeg");

// Return the processed image directly
ob_start();
$temp_image->writeToBuffer(".jpg");
$imageData = ob_get_clean();
echo $imageData;

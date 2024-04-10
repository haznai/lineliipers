<?php

require "vendor/autoload.php";

use Jcupitt\Vips;
use Symfony\Component\Console\Output\Output;

// @todo: whole script is a mess, needs to be cleaned up

// 1. Initialize Parameters

$stripe_thickness = 4; // adjust as needed
// @todo jeremie gave a formulation for num of stripes in figma
$num_of_stripes = 20; // adjust as needed
$angle = 67; // adjust in degrees, as needed

// 2. load in test image
$test_image_path = "test_images/steffi.jpg";
$input_image = Vips\Image::newFromFile($test_image_path, [
    "access" => "random",
]);

// 3. turn image grayscale
$input_image = $input_image->colourspace("b-w");

// preprocess image curves
function preprocess_image(Vips\Image $image)
{
    // create a tone adjustment operation, equivalent to tone_lut() in libvips
    //$tonelut_operation = Vips\VipsOperation::newFromName("tonelut");
    //  $tonelut_operation->callBase();

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
$temp_image->writeToFile("testing_results/output.jpg");

// output original image
$input_image->writeToFile("testing_results/original_image.jpg");

// output original stripes
$stripe->writeToFile("stripe.jpg");

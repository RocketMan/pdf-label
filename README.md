# PDF_Label

This class is a modified version of `PDF_Label` that adds support for
unicode and ttf.

Documentation and source for the upstream class can be found here:
http://www.fpdf.org/en/script/script29.php


## Installation with [Composer](https://packagist.org/packages/rocketman/pdf-label)

If you are using composer to manage dependencies, you can use

    $ composer require rocketman/pdf-label:1.6+rocketman.1

or you can include the following in your `composer.json` file:

```json
{
    "require": {
        "rocketman/pdf-label": "1.6+rocketman.1"
    }
}
```

## Usage

```php
$label = "5160"; // pre-defined label name or form-spec array
$pdf = new \PDF_Label($label);
$pdf->AddFont(...); // see tFPDF documentation for AddFont and SetFont
$pdf->SetFont(...);
$pdf->AddPage();
$pdf->Add_Label("label content here");
$pdf->Add_Label("second label");
$pdf->Output();
```

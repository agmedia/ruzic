<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


$ukupnohub = '200.05';
$pozivnabroj = '15-2022';

 $hubstring = array (
                    'renderer' => 'image',
                    'options' =>
                        array (
                            "format" => "jpg",
                            "scale" =>  3,
                            "ratio" =>  3,
                            "color" =>  "#2c3e50",
                            "bgColor" => "#fff",
                            "padding" => 20
                        ),
                    'data' =>
                        array (
                            'amount' => floatval($ukupnohub),
                            'sender' =>
                                array (
                                    'name' => 'Tomislav jureša',
                                    'street' => 'Zapoljska 20',
                                    'place' => '10000 Zagreb',
                                ),
                            'receiver' =>
                                array (
                                    'name' => 'AMO DIZAJN JJ d.o.o.',
                                    'street' => 'Kamenarka 11',
                                    'place' => '10010 Zagreb',
                                    'iban' => 'HR2124840081105798322',
                                    'model' => '00',
                                    'reference' => $pozivnabroj,
                                ),
                            'purpose' => 'CMDT',
                            'description' => 'Web narudžba AMDS Jeans',
                        ),
                );


print_r($hubstring);



                $postString = json_encode($hubstring);

                $url = 'https://hub3.bigfish.software/api/v1/barcode';
                $ch = curl_init($url);

                # Setting our options
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $postString);
                curl_setopt( $ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
               curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
               curl_setopt($ch, CURLOPT_TIMEOUT, 300); //timeout in seconds

                # Get the response

               $errors = curl_error($ch);

               $response = curl_exec($ch);
               curl_close($ch);

               //var_dump($errors);
               var_dump($response);


                $json = json_decode($response);



?>
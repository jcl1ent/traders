<?php
$page_title="Products and Services";
include("includes/header.php");
include("includes/navbar.php");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="style.css">
    <style>
        html, body {
            height: 100%; /* Ensures the body and html are 100% of the viewport */
            margin: 0;
            padding: 0;
        }
        body {
            background-image: url('images_productsAndservices/RONYX TRADING ENGINEERING SERVICES.png'); /* Path to your image */
            background-size: cover; /* Ensure the image covers the entire page */
            background-position: center; /* Center the image */
            background-repeat: no-repeat; /* Prevents the image from repeating */
            background-attachment: fixed; /* Makes the background image stay fixed while scrolling */
        }        

        .header {
            background: rgba(255, 255, 255, 0.1); /* Light transparent white for glass effect */
            padding: 3rem; /* Increased padding */
            border-radius: 15px;
            backdrop-filter: blur(5px); /* Makes the background blurry */
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2); /* Increased shadow for depth */
            max-width: auto; /* Increased max width */
            width: 100%; /* Full width on smaller screens */
            
        }
        .header h1{
            font-family: "myFont";
            src: url("myFont.woff2");
        }

    
    </style>
</head>
<body>
    <div class="header">
        <h1 class="text-light">PRODUCTS</h1>
        <div class="wrapper">
            <div class="image">
                <img src="images_productsAndservices\Akasaka AH-38 Cylinder Head.jpg">
                <div class="content">
                    <h2>Akasaka AH-38 Cylinder Head</h2>
                    <p> A component typically associated with small,
                        high-performance engines. Akasaka is known for producing 
                        precision-engineered parts for various types of machinery, 
                        including racing and high-performance engines.
                    </p>
                </div>
            </div>
            <div class="image">
                <img src="images_productsAndservices\nozzle ring.jpg">
                <div class="content">
                    <h2>Nozzle Ring</h2>
                    <p> Nozzle rings are critical. In your powerplant turbochargers, 
                        they directly convert exhaust gas energy into kinetic energy and power.
                    </p>
                </div>
                
                
            </div>
            <div class="image">
                <img src="images_productsAndservices\rocker_arms.jpg">
                <div class="content">
                    <h2>Rocker Arms</h2>
                    <p> The rocker arm is the part responsible for
                        transmitting the movement of the camshaft 
                        towards the intake and exhaust valves of the engine.
                    </p>
                </div>
            </div>
        </div>
        <div class="wrapper1">
            <div class="image">
                <img src="images_productsAndservices\valve_cage.jpg">
                <div class="content">
                    <h2>Valve Cage</h2>
                    <p> A cylinder fitted with ports in which a valve plug moves.
                        The port openings are shaped to produce various flow 
                        characteristics for different valves, e.g. linear or equal 
                        percentage.</p>
                </div>
            </div>
            <div class="image">
                <img src="images_productsAndservices\turbine_blade.jpg">
                <div class="content">
                    <h2>Turbine Blade</h2>
                    <p> A turbine blade is a radial aerofoil mounted 
                        in the rim of a turbine disc and which produces 
                        a tangential force which rotates a turbine rotor.</p>
                </div>
            </div>
            <div class="image">
                <img src="images_productsAndservices\piston_crown.jpg">
                <div class="content">
                    <h2>Piston Crown</h2>
                    <p> also known as piston head, is the top end from 
                        a complete piston and is exposed in a high level 
                        to hot gases within the combustion chamber.</p>
                </div>
            </div>
        </div>
    </div>
    <div class="header">
        <h1 class="text-light">SERVICES</h1>
        <div class="wrapper">
            <div class="image">
                <img src="images_productsAndservices\welding_technology.jpg">
                <div class="content">
                    <h2>Welding Tech<br>nology</h2>
                    <p> for joining materials,
                        typically metals or thermoplastics, by using high heat to melt 
                        the parts together and allowing them to cool, forming a strong joint. 
                    </p>
                </div>
            </div>
            <div class="image">
                <img src="images_productsAndservices\turbocharger.jpg">
                <div class="content">
                    <h2>Turbo<br>charger</h2>
                    <p> Use to increase its
                        efficiency and power output. It uses the exhaust gas from the engine to
                        drive a turbine that compresses air into the combustion chamber, enhancing
                        the engine's performance.
                    </p>
                </div>
            </div>
            <div class="image">
                <img src="images_productsAndservices\Cylinder_block.jpg">
                <div class="content">
                    <h2>Cylinder Block</h2>
                    <p> The main structure of the engine housing of the cylinders.</p>
                </div>
            </div>
        </div>
        <div class="wrapper1">
            <div class="image">
                <img src="images_productsAndservices\Cylinder_block.jpg">
                <div class="content">
                    <h2>Cylinder Head</h2>
                    <p> Sits atop the cylinder block and contains the combustion chamber.</p>
                </div>
            </div>
            <div class="image">
                <img src="images_productsAndservices\piston.jpg">
                <div class="content">
                    <h2>Piston</h2>
                    <p> Moves up and down inside the cylinder, compressing air and fuel for combustion.</p>
                </div>
            </div>
            <div class="image">
                <img src="images_productsAndservices\Crankshaft.jpg">
                <div class="content">
                    <h2>Crankshaft</h2>
                    <p> Converts the piston's up-and-down motion into rotational motion.</p>
                </div>
            </div>
        </div>
        <div class="wrapper1">
            <div class="image">
                <img src="images_productsAndservices\Camshaft.jpg">
                <div class="content">
                    <h2>Camshaft</h2>
                    <p> Operates the intake and exhaust valves in sync with the engine cycle.</p>
                </div>
            </div>
            <div class="image">
                <img src="images_productsAndservices\casting.jpg">
                <div class="content">
                    <h2>Casting </h2>
                    <p> manufacturing process where molten material, usually metal, 
                        is poured into a mold and allowed to cool, solidifying into the desired 
                        shape. Commonly used to produce complex metal parts.</p>
                </div>
            </div>
            <div class="image">
                <img src="images_productsAndservices\Surface_Alloying_of_Big_Diesel_Components.jpg">
                <div class="content">
                    <h2>Surface Alloying</h2>
                    <p>enhances the wear and corrosion resistance of large diesel engine components 
                        by applying a layer of alloy material through welding or laser techniques 
                        to improve surface properties.</p>
                </div>
            </div>
        </div>
        <div class="wrapper1">
            <div class="image">
                <img src="images_productsAndservices\gas_turbine.jpg">
                <div class="content">
                    <h2>Turbine</h2>
                    <p> Driven by exhaust gases to spin the compressor.</p>
                </div>
            </div>
            <div class="image">
                <img src="images_productsAndservices\compressor.jpg">
                <div class="content">
                    <h2>Compressor </h2>
                    <p>Increases the amount of air that is fed into the engine’s cylinders.</p>
                </div>
            </div>
            <div class="image">
                <img src="images_productsAndservices\Bearing_System.jpg">
                <div class="content">
                    <h2>Bearing System</h2>
                    <p>Supports the rotating shaft between the turbine and compressor.</p>
                </div>
            </div>
        </div>
        <div class="wrapper1">
            <div class="image">
                <img src="images_productsAndservices\In-Place_Dynamic_Balancing.jpg">
                <div class="content">
                    <h2>In-Place Dynamic Balancing</h2>
                    <p>adjusts mass distribution during operation to prevent 
                        vibrations and ensure smooth performance in large machinery.</p>
                </div>
            </div>
            <div class="image">
                <img src="images_productsAndservices\Steam_Turbine_Rotor.jpg">
                <div class="content">
                    <h2>Steam Turbine Rotor</h2>
                    <p>A steam turbine rotor converts steam's thermal energy into mechanical energy for power generation.</p>
                </div>
            </div>
            <div class="image">
                <img src="images_productsAndservices\dynamic_balancing.jpg">
                <div class="content">
                    <h2>Dynamic Balancing</h2>
                    <p>Corrects imbalances in rotating parts during operation by adding or removing mass from specific areas.</p>
                </div>
            </div>
        </div>
        <div class="wrapper1">
            <div class="image">
                <img src="images_productsAndservices\fusion_welding.jpg">
                <div class="content">
                    <h2>Fusion Welding</h2>
                    <p>involves melting and fusing materials together using heat, 
                        without requiring filler materials. Examples include arc and gas welding.</p>
                </div>
            </div>
            <div class="image">
                <img src="images_productsAndservices\Low_Heat_Input_Welding.jpg">
                <div class="content">
                    <h2>Low Heat Input Welding</h2>
                    <p>This welding technique uses minimal heat to reduce distortion and 
                        avoid weakening the metal, ideal for delicate or high-strength applications.</p>
                </div>
            </div>
            <div class="image">
                <img src="images_productsAndservices\In-Place Grinding.jpg">
                <div class="content">
                    <h2>In-Place Grinding</h2>
                    <p>In-place grinding involves grinding machine parts without disassembly,
                        used to repair surfaces like turbine shafts to ensure smooth operation.</p>
                </div>
            </div>
        </div>
        <div class="wrapper1">
            <div class="image">
                <img src="images_productsAndservices\casing.jpg">
                <div class="content">
                    <h2>Casing</h2>
                    <p>The outer protective housing of a mechanical part, often designed to
                         enclose and protect moving or delicate components.</p>
                </div>
            </div>
            <div class="image">
                <img src="images_productsAndservices\Machining.jpg">
                <div class="content">
                    <h2>Machining</h2>
                    <p>Refers to the process of shaping a material into a desired form using cutting tools, drills, or lathes.</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
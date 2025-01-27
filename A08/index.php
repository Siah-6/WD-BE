<?php
include('connect.php');
include('classes.php');

$cards = array();
$islandQuery = "SELECT * FROM islandsofpersonality";
$islandResult = executeQuery($islandQuery);
$islandCount = mysqli_num_rows($islandResult);

while ($islandRow = mysqli_fetch_assoc($islandResult)) {
    $c = new island(
        $islandRow['islandOfPersonalityID'],
        $islandRow['name'],
        $islandRow['shortDescription'],
        $islandRow['longDescription'],
        $islandRow['color'],
        $islandRow['image']
    );
    array_push($cards, $c);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Core Memories</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="Icon" type="image/png" href="img/logo.png">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background-image: url('img/blurbg.jpeg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            color: #fff;
            min-height: 100vh;
        }

        .header {
            background: rgba(137, 164, 227, 0.8);
            backdrop-filter: blur(10px);
            padding: 1rem 0;
            position: fixed;
            width: 100%;
            z-index: 1000;
        }

        .container {
            width: 90%;
            max-width: 1400px;
            margin: 0 auto;
        }

        .nav-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 600;
            color: #fff;
            text-decoration: none;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }

        .hero {
            padding-top: 120px;
            text-align: center;
            margin-bottom: 4rem;
        }

        .hero h1 {
            font-size: 4rem;
            margin-bottom: 1rem;
            color: #CCE5FF;
            text-shadow: 0 0 10px rgba(204, 229, 255, 0.5);
            animation: glow 2s ease-in-out infinite alternate;
        }

        @keyframes glow {
            from {
                text-shadow: 0 0 10px rgba(204, 229, 255, 0.5);
            }

            to {
                text-shadow: 0 0 20px rgba(204, 229, 255, 0.8);
            }
        }

        .hero p {
            color: rgba(255, 255, 255, 0.9);
            max-width: 600px;
            margin: 0 auto;
            line-height: 1.6;
        }

        .memory-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 2rem;
            padding: 2rem 0;
        }

        .memory-card {
            position: relative;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            overflow: hidden;
            transition: all 0.4s ease;
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .memory-card::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg,
                    transparent,
                    rgba(255, 255, 255, 0.1),
                    transparent);
            transform: rotate(45deg);
            transition: 0.6s;
            opacity: 0;
        }

        .memory-card:hover::before {
            animation: shine 1.5s;
        }

        @keyframes shine {
            0% {
                transform: rotate(45deg) translateX(-100%);
                opacity: 0;
            }

            50% {
                opacity: 0.3;
            }

            100% {
                transform: rotate(45deg) translateX(100%);
                opacity: 0;
            }
        }

        .memory-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.3);
            border-color: rgba(255, 255, 255, 0.4);
        }

        .memory-card img {
            width: 100%;
            height: 250px;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .memory-card:hover img {
            transform: scale(1.1);
        }

        .memory-content {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 1.5rem;
            background: linear-gradient(to top,
                    rgba(0, 0, 0, 0.9),
                    rgba(0, 0, 0, 0.6),
                    transparent);
            transform: translateY(100%);
            transition: transform 0.3s ease;
        }

        .memory-card:hover .memory-content {
            transform: translateY(0);
        }

        .memory-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #fff;
        }

        .memory-desc {
            color: rgba(255, 255, 255, 0.9);
            font-size: 0.9rem;
            margin-bottom: 1rem;
            line-height: 1.5;
        }

        .memory-link {
            display: inline-block;
            padding: 0.6rem 1.8rem;
            background: rgba(137, 164, 227, 0.8);
            color: #fff;
            text-decoration: none;
            border-radius: 25px;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .memory-link:hover {
            background: rgba(137, 164, 227, 1);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(137, 164, 227, 0.4);
        }

        .footer {
            text-align: center;
            padding: 2rem 0;
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.9rem;
            background: rgba(0, 0, 0, 0.3);
            margin-top: 3rem;
        }

        @media (max-width: 1200px) {
            .memory-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (max-width: 900px) {
            .memory-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 600px) {
            .memory-grid {
                grid-template-columns: 1fr;
            }

            .hero h1 {
                font-size: 2.5rem;
            }
        }
    </style>
</head>

<body>
    <header class="header">
        <div class="container">
            <div class="nav-content">
                <a href="index.php" class="logo">Core Memories</a>
                <span class="author" style="color: #fff;">Jon Josiah</span>
            </div>
        </div>
    </header>

    <main>
        <section class="hero">
            <div class="container">
                <h1>CORE MEMORIES</h1>
                <p>More Memories to Come ^^ </p>
            </div>
        </section>

        <div class="container">
            <div>
                <?php $islandCount ?>
            </div>

            <div class="memory-grid">
                <?php foreach ($cards as $card) { ?>
                    <article class="memory-card">
                        <img src="img/<?php echo $card->image; ?>" alt="<?php echo $card->name; ?>">
                        <div class="memory-content">
                            <h2 class="memory-title"><?php echo $card->name; ?></h2>
                            <p class="memory-desc"><?php echo $card->shortDesc; ?></p>
                            <a href="view.php?id=<?php echo $card->id; ?>" class="memory-link">View</a>
                        </div>
                    </article>
                <?php } ?>
            </div>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <p>© 2024</p>
        </div>
    </footer>
    
</body>

</html>
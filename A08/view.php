<?php
include('connect.php');
include('classes.php');

$islandID = $_GET['id'];
$islandInfo = null;

$islandQuery = "SELECT * FROM islandsofpersonality WHERE islandOfPersonalityID = '$islandID'";
$islandResult = executeQuery($islandQuery);

while ($islandRow = mysqli_fetch_assoc($islandResult)) {
    $islandInfo = new island(
        $islandRow['islandOfPersonalityID'],
        $islandRow['name'],
        $islandRow['shortDescription'],
        $islandRow['longDescription'],
        $islandRow['color'],
        $islandRow['image']
    );
}

$contentArray = array();
$islandContentQuery = "SELECT * FROM islandcontents WHERE islandOfPersonalityID = '$islandID'";
$islandContentResult = executeQuery($islandContentQuery);
$islandContentCount = mysqli_num_rows($islandContentResult);

while ($islandContentRow = mysqli_fetch_assoc($islandContentResult)) {
    $ic = new islandContent(
        $islandInfo->color,
        $islandContentRow['image'],
        $islandContentRow['islandOfPersonalityID'],
        $islandContentRow['content']
    );
    array_push($contentArray, $ic);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $islandInfo->name ?> - Core Memories</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
            background: rgba(137, 164, 227, 0.95);
            padding: 0;
            position: fixed;
            width: 100%;
            z-index: 1000;
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
            transform: translateY(0);
        }

        .nav-content {
            height: 70px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 2rem;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 600;
            color: #fff;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .logo i {
            font-size: 1.8rem;
        }

        .memory-header {
            padding-top: 100px;
            text-align: center;
            margin-bottom: 2rem;
        }

        .memory-title {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: #fff;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }

        .memory-description {
            max-width: 800px;
            margin: 0 auto;
            color: rgba(255, 255, 255, 0.9);
            line-height: 1.6;
        }

        .memory-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            padding: 2rem;
            max-width: 1400px;
            margin: 0 auto;
            justify-content: center;
        }


        .memory-card {
            position: relative;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            overflow: hidden;
            transition: all 0.4s ease;
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            aspect-ratio: 1;
        }

        .memory-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
        }

        .memory-card img {
            width: 100%;
            height: 100%;
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

        .memory-text {
            color: #fff;
            font-size: 1rem;
            line-height: 1.5;
            text-align: center;
        }

        .back-button {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            background: rgba(137, 164, 227, 0.9);
            color: #fff;
            padding: 1rem;
            border-radius: 50%;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
        }

        .back-button:hover {
            transform: translateY(-5px);
            background: rgba(137, 164, 227, 1);
        }

        .footer {
            text-align: center;
            padding: 2rem 0;
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.9rem;
            background: rgba(0, 0, 0, 0.3);
            margin-top: 3rem;
        }

        @media (max-width: 768px) {
            .memory-grid {
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                padding: 1rem;
            }

            .memory-title {
                font-size: 2rem;
            }
        }
    </style>
</head>

<body>
    <header class="header">
        <div class="nav-content">
            <a href="index.php" class="logo">
                <i class="fas fa-brain"></i>
                Core Memories
            </a>
        </div>
    </header>

    <main>
        <section class="memory-header">
            <h1 class="memory-title"><?php echo $islandInfo->name ?></h1>
            <p class="memory-description"><?php echo $islandInfo->longDesc ?></p>
        </section>

        <div class="memory-grid">
            <?php foreach ($contentArray as $card) { ?>
                <div class="memory-card">
                    <img src="img/<?php echo $card->image; ?>" alt="Memory Image">
                    <div class="memory-content">
                        <p class="memory-text"><?php echo $card->content; ?></p>
                    </div>
                </div>
            <?php } ?>
        </div>
    </main>

    <a href="index.php" class="back-button">
        <i class="fas fa-arrow-left"></i>
    </a>

    <footer class="footer">
        <p>© 2024</p>
    </footer>

    <script>
        let lastScrollTop = 0;
        const header = document.querySelector('.header');

        window.addEventListener('scroll', () => {
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            if (scrollTop > lastScrollTop) {
                header.style.transform = 'translateY(-100%)';
            } else {
                header.style.transform = 'translateY(0)';
            }
            lastScrollTop = scrollTop;
        });
    </script>

</body>

</html>
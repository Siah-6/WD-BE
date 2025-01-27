<?php
class island
{
    public $id;
    public $name;
    public $shortDesc;
    public $longDesc;
    public $color;
    public $image;

    public function __construct($id, $name, $shortDesc, $longDesc, $color, $image)
    {
        $this->id = $id;
        $this->name = $name;
        $this->shortDesc = $shortDesc;
        $this->longDesc = $longDesc;
        $this->color = $color;
        $this->image = $image;
    }

    public function buildCard()
    {
        return '
        <div class="col">
            <div class="card card-bg overflow-hidden" style="background:radial-gradient(' . $this->color . ')">
                <img src="img/' . $this->image . '" class="card-img-top" alt="...">
                <div class="card-body">
                    <h5 class="card-title">' . $this->name . '</h5>
                    <p class="card-text">' . $this->shortDesc . '</p>
                    <a href="view.php?id=' . $this->id . '" class="btn btn-primary">Go here</a>
                </div>
            </div>
        </div>';
    }
}

class islandContent
{
    public $color;
    public $islandOfPersonalityID;
    public $image;
    public $content;

    public function __construct($color, $image, $islandOfPersonalityID, $content)
    {
        $this->color = $color;
        $this->image = $image;
        $this->islandOfPersonalityID = $islandOfPersonalityID;
        $this->content = $content;
    }

    public function buildCard()
    {
        return '
        <div class="card my-5">
            <div class="row g-0" style="background:radial-gradient(' . $this->color . ');">
                <div class="col-12 col-md-6">
                    <div class="img-featured-container">
                        <img src="contentpic/' . $this->image . '" alt="Featured Image" class="img-fluid">
                    </div>
                </div>
                <div class="col-12 col-md-6">
                    <div class="card-body">
                        <p class="card-text">' . $this->content . '</p>
                    </div>
                </div>
            </div>
        </div>';
    }
}
?>
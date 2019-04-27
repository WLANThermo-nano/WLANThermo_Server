<?php
 
class Raumschiff_fabrik
{
    public $geschwindigkeit = 5;
    public $schild = 3;
    public $leben = 10;
     
    public $name;
     
    public function setName($neuer_name) {
        $this->name = $neuer_name;
    }
     
    public function treffer($schaden) {
        //prüfe ob der Asteroid unser Schutzschild durchschlagen hat 
        if($this->schild < $schaden)
        {
            //berechen wieviel Schaden trotz des Schilds entsteht
            $rest_schaden = $schaden - $this->schild;
            $this->leben -= $rest_schaden;
                 
            if($this->leben <= 0)
            {
                $this->leben = 0;
            }
        }
    }
     
    public function status() {
        if($this->leben > 0)
        {
            return 'Leben: '.$this->leben;
        } else {
            return 'zerstoert';
        }
    }
}
 
$schiff1 = new Raumschiff_fabrik();
$schiff2 = new Raumschiff_fabrik();
$schiff1->setName('Anubis');
$schiff2->setName('Cherti');
 
$runde = 1;
 
// unser Pseudo-Spiel soll 10 Runden dauern
while($runde <= 10)
{
    echo '<strong>Runde'.$runde.'</strong><br />';
     
    // erzeuge einen zufälligen Asteroiden
    $asteroid = rand(1, 10);
     
    // Schiff1: prüfe Kollision (Wahrscheinlichkeit 33%), ziehe Leben ab
    if($schiff1->leben > 0) 
    {
        if(rand(1,3) == 1) {
            $schiff1->treffer($asteroid);
            echo $schiff1->name.' wurde getroffen. Asteroid: '.$asteroid.' - Leben: '.$schiff1->leben.'<br />';
        }
    }
     
    // Schiff2: prüfe Kollision (Wahrscheinlichkeit 33%), ziehe Leben ab
    if($schiff2->leben > 0)
    {
        if(rand(1,3) == 1) {
            $schiff2->treffer($asteroid);
            echo $schiff2->name.' wurde getroffen. Asteroid: '.$asteroid.' - Leben: '.$schiff2->leben.'<br />';
        }
    }
     
    echo '<br />';        
             
    // nächste Runde        
    $runde++;
}
 
echo '<hr />';
 
//Gib nach den 10 Runden den Status beider Schiffe aus
echo 'Raumschiff "'.$schiff1->name.'" - '.$schiff1->status()."<br />";
echo 'Raumschiff "'.$schiff2->name.'" - '.$schiff2->status();
 
?>
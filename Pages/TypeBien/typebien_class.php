<?php
class TypeBien
{
    private $id_typebien;
    private $des_typebien;

    public function __construct($id_typebien = null, $des_typebien = "")
    {
        $this->id_typebien = $id_typebien;
        $this->des_typebien = $des_typebien;
        $this->des_typebien = $des_typebien;
    }

    // --- Getters ---
    public function getIdTypeBien()
    {
        return $this->id_typebien;
    }

    public function getDesTypeBien()
    {
        return $this->des_typebien;
    }

    // --- Setters ---
    public function setIdTypeBien($id_typebien)
    {
        $this->id_typebien = $id_typebien;
    }

    public function setDesTypeBien($des_typebien)
    {
        $this->des_typebien = $des_typebien;
    }
}
?>

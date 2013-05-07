<?php
/**
 * Biblioteka KwotaSlownie
 * 
 * Przede wszystkim potrafi zamienic kwote z postaci liczbowa na postac slowna, 
 * Z ktora na pewno kazdy sie spotkal na rachunkach lub fakturach
 * Biblioteka obsluguje rozne typu danych string, integer, float
 * Dlatego mozna podawac jej kwote z kropka, z przecinkiem lub liczbe calkowita
 * Z kazda z nich biblioteka sobie poradzi wlaczajac w to oczywiscie kwoty ujemne.
 * Biblioteka potrafi takze prawidlowo odmieniac po polsku (tysiac, tysiecy) itd. 
 * Dzieki zapewnionej konfigurowalnosci mozna decydowac czy kwota zdawkowa 
 * Ma byc prezentowana tak jak kwota podstawowa, czy tez w formie liczbowej
 * Mozna takze okreslic w bibliotece walute, gdy potrzebujesz uzyc innej niz polski zloty
 * Bez problemu mozemy nakazac jej odmieniac dolary, euro, jeny czy jakakolwiek inna walute
 * Tyczy sie to takze kwoty zdawkowej czyli grosze, centy, pensy
 * 
 * @link        http://www.kwotaslownie.pl
 * @author        Maciej Strączkowski <m.straczkowski@gmail.com>
 * @copyright    Maciej Strączkowski
 * @category    Libraries
 * @since        06.11.2011
 * @version        1.4 [17.08.2012]
 * @license        LGPL
 */
class KwotaSlownie extends CI_Model{
    
    // Wlasciwosc przechowujaca skladowe
    private $_aComponents = array();
    
    // Tablica przechowujaca poformatowane czesci kwoty
    private $_aOutput = array();
    
    // Czy kwota zdawkowa ma byc takze konwertowana na slowa
    private $_bRestWords = true;
    
// --------------------------------------------------------------------
    
    /**
     * Metoda __construct();
     * 
     * Metoda tworzy wlasciwosc prywatna, ktora jest tablica
     * Zawiera ona czesci skladowe cen w postaci slownej
     * Te dane sa zapisywane jako wlasciwosc, aby miec do nich
     * Dostep w obrebie calej projektowanej biblioteki
     * 
     * @access    public
     * @return    void
     */
    public function __construct()
    {
        $this->_aComponents = array(
            'unities' => array(
                'zero', 'jeden', 'dwa', 'trzy', 'cztery', 'pięć', 
                'sześć', 'siedem', 'osiem', 'dziewięć', 'dziesięć', 
                'jedenaście', 'dwanaście', 'trzynaście', 'czternaście', 
                'piętnaście', 'szesnaście', 'siedemnaście', 'osiemnaście', 
                'dziewiętnaście'
             ),
            'tens' => array(
                '', 'dziesięć', 'dwadzieścia', 'trzydzieści', 'czterdzieści', 
                'pięćdziesiąt', 'sześćdziesiąt', 'siedemdziesiąt', 'osiemdziesiąt', 
                'dziewięćdziesiąt'
             ),
            'hundreds' => array(
                '', 'sto', 'dwieście', 'trzysta', 'czterysta', 
                'pięćset', 'sześćset', 'siedemset', 'osiemset', 
                'dziewięćset'
             ),
            'thousands' => array(
                'tysiąc', 'tysiące', 'tysięcy'
             ),
            'milions' => array(
                'milion', 'miliony', 'milionów'
             ),
            'billions' => array(
                'miliard', 'miliardy', 'miliardów'
             ),
            'currency' => array(
                 'złoty', 'złote', 'złotych'
             ),
            'currency_rest' => array(
                'grosz', 'grosze', 'groszy'
             )
        );
    }//end of __construct() method
    
// --------------------------------------------------------------------
    
    /**
     * Metoda setCasualMode();
     * 
     * Metoda pozwala ustawic czy kwota zdawkowa ma byc konwertowana
     * Tak samo jak kwota podstawowa na postac slowna
     * Czy tez ma byc konwertowana na postac liczbowa (np. 10/100)
     * Jezeli zostanie podana wartosc inna wartosc niz (text lub number)
     * Zostanie ustawiona wartosc domyslna czyli konwersja slowna
     * 
     * @access    public
     * @param    string    $sMode - text (slownie) lub number (liczbowo)
     * @return    object
     */
    public function setCasualMode($sMode = 'text')
    {
        switch($sMode)
        {
            case 'text':    $this->_bRestWords = true;    break;
            case 'number':    $this->_bRestWords = false;break;
            default:            $this->_bRestWords = true;    break;
        }
        return $this;
    }//end of setCasualMode() method

// --------------------------------------------------------------------
    
    /**
     * Metoda setCurrency();
     * 
     * Metoda pozwala na reczne ustawienie waluty przez uzytkownika
     * Mozna zdefiniowac inna walute niz domyslne zlotowki/grosze
     * Nalezy przekazac metodzie dwa parametry pierwszy jest tablica 
     * Zawierajaca odmiany waluty kwoty podstawowej, drugi jest tablica
     * Zawierajaca odmiany waluty kwoty zdawkowej, czyli dla przykladu 
     * $aPrimary = array('dolar', 'dolary', 'dolarów');
     * $aSecondary = array('cent', 'centy', 'centów');
     * 
     * @access    public
     * @param    array        $aPrimary    - Tablica z odmianami waluty kwoty podstawowej
     * @param    array        $aSecondary - Tablica z odmianami waluty kwoty zdawkowej
     * @return    object 
     */
    public function setCurrency($aPrimary, $aSecondary)
    {
        $this->_aComponents['currency'] = $aPrimary;
        $this->_aComponents['currency_rest'] = $aSecondary;
        return $this;
    }//end of setCurrency()
    
// --------------------------------------------------------------------
    
    /**
     * Metoda convertPrice();
     * 
     * Metoda dokonuje konwersji przekazanej kwoty z postaci liczbowej
     * Na postac slowna, uzywajac do tego celu metod prywatnych
     * Automatycznie zamienia przecinki na kropki oraz zaokragla kwote
     * Do dwoch miejsc po przecinku
     * 
     * @access    public
     * @param    integer    $fPrice    - Kwota do zamiany
     * @return    string    - Kwota przedstawiona w postaci slownej
     */
    public function convertPrice($fPrice)
    {
        $fPrice = str_replace(',', '.', $fPrice);
            if(!is_numeric($fPrice)){
                return '';
            }
            $fPrice = number_format($fPrice, 2, '.', '');
            if($fPrice >= 1000000000000 || $fPrice <= -1000000000000){
                return '';
            }
            if($fPrice < 0){
                $this->_aOutput[] = 'minus';
                $fPrice = $fPrice*-1;
                $fPrice = number_format($fPrice, 2, '.', '');
            }
        $aParts = explode('.', $fPrice);
        $iFirst = $aParts[0];
            if(isset($aParts[1]) && $aParts[1] == '00'){
                unset($aParts[1]);
            }
            if(isset($aParts[1])){
                $iSecond = $aParts[1];
                if(strlen($iSecond) < 2){
                    $iSecond = $iSecond.'0';
                }
            }
            else {
                $iSecond = 0;
            }
        self::$this->_convertRouter($iFirst);
        $this->_convertVariety($iFirst, 'currency');
            if($this->_bRestWords === true){
                $this->_convertRouter($iSecond);
            }
            else {
                $this->_aOutput[] = $iSecond.'/100';
            }
        $this->_convertVariety($iSecond, 'currency_rest');
        $sReturn = implode(' ', $this->_aOutput);
        unset($this->_aOutput);
        return $sReturn;
    }//end of convertPrice() method.
    
// --------------------------------------------------------------------
    
    /**
     * Metoda _convertRouter();
     * 
     * Metoda okresla ilosc znakow wystepujacych w przekazanej kwocie
     * Na jej podstawie decyduje co w danej chwili trzeba konwertowac
     * Ilosc znakow > 9 - konwertuj miliardy
     * Ilosc znakow >= 7 - konwertuj miliony
     * Ilosc znakow >= 4 - konwertuj tysiace
     * Ilosc znakow >= 3 - konwertuj setki
     * Ilosc znakow >= 2 - konwertuj dziesiatki
     * Ilosc znakow >= 1 - konwertuj jednostki
     * 
     * @access    protected
     * @param    integer    $fPrice    - Kwota do zamiany
     * @return    boolean
     */
    protected function _convertRouter($fPrice)
    {
        $iLenght = strlen($fPrice);
        if($iLenght > 9){
            $this->_convertBillions($fPrice, $iLenght);
            return true;
        }
        elseif($iLenght >= 7) {
            $this->_convertMilions($fPrice, $iLenght);
            return true;
        }
        elseif($iLenght >= 4) {
            $this->_convertThousands($fPrice, $iLenght);
            return true;
        }
        elseif($iLenght >= 3) {
            $this->_convertHundreds($fPrice);
            return true;
        }
        elseif($iLenght >= 2) {
            $this->_convertTens($fPrice);    
            return true;
        }
        elseif($iLenght >= 1) {
            $this->_convertUnities($fPrice);
            return true;
        }
        return false;
    }//end of _convertRouter() method.
    
// --------------------------------------------------------------------
    
    /**
     * Metoda _convertBillions();
     * 
     * Metoda zapisuje ilosc znakow wystepujacych w przekazanej kwocie
     * Na jej podstawie decyduje jakie czesci trzeba obciac za pomoca substr
     * Obciete czesci kwoty ponownie sa wysylana do routera
     * Dodatkowo dobierana jest poprawna odmiana slowa "miliard"
     * 
     * @access    protected
     * @param    integer    $fPrice    - Kwota
     * @param    integr    $iLength    - Dlugosc
     * @return    boolean
     */
    protected function _convertBillions($fPrice, $iLength)
    {
        if($iLength >= 12) {
            $iSliced = substr($fPrice, -12, 3);
            $iNextSliced = substr($fPrice, 3, 12);
        }
        elseif($iLength >= 11) {
            $iSliced = substr($fPrice, -11, 2);
            $iNextSliced = substr($fPrice, 2, 11);
        }
        elseif($iLength >= 10) {
            $iSliced = substr($fPrice, -10, 1);
            $iNextSliced = substr($fPrice, 1, 10);
        }
        else {
            return false;
        }
        
        if($iSliced != 1){
            $this->_convertRouter($iSliced);
        }
        if($iSliced != 0){
            $this->_convertVariety($iSliced, 'billions');
        }
        $this->_convertRouter($iNextSliced);
        return true;
    }//end of _convertBillions() method.
    
// --------------------------------------------------------------------
    
    /**
     * Metoda _convertMilions();
     * 
     * Metoda zapisuje ilosc znakow wystepujacych w przekazanej kwocie
     * Na jej podstawie decyduje jakie czesci trzeba obciac za pomoca substr
     * Obciete czesci kwoty ponownie sa wysylana do routera
     * Dodatkowo dobierana jest poprawna odmiana slowa "milion"
     * 
     * @access    protected
     * @param    integer    $fPrice    - Kwota
     * @param    integr    $iLength    - Dlugosc
     * @return    boolean
     */
    protected function _convertMilions($fPrice, $iLength)
    {
        if($iLength >= 9) {
            $iSliced = substr($fPrice, -9, 3);
            $iNextSliced = substr($fPrice, 3, 9);
        }
        elseif($iLength >= 8) {
            $iSliced = substr($fPrice, -8, 2);
            $iNextSliced = substr($fPrice, 2, 8);
        }
        elseif($iLength >= 7) {
            $iSliced = substr($fPrice, -7, 1);
            $iNextSliced = substr($fPrice, 1, 7);
        }
        else {
            return false;
        }
        
        if($iSliced != 1){
            $this->_convertRouter($iSliced);
        }
        if($iSliced != 0){
            $this->_convertVariety($iSliced, 'milions');
        }
        $this->_convertRouter($iNextSliced);
        return true;
    }//end of _convertMilions() method.
    
// --------------------------------------------------------------------
    
    /**
     * Metoda _convertThousands();
     * 
     * Metoda zapisuje ilosc znakow wystepujacych w przekazanej kwocie
     * Na jej podstawie decyduje jakie czesci trzeba obciac za pomoca substr
     * Obciete czesci kwoty ponownie sa wysylana do routera
     * Dodatkowo dobierana jest poprawna odmiana slowa "tysiac"
     * 
     * @access    protected
     * @param    integer    $fPrice    - Kwota
     * @param    integr    $iLength    - Dlugosc
     * @return    boolean
     */
    protected function _convertThousands($fPrice, $iLength)
    {
        if($iLength >= 6) {
            $iSliced = substr($fPrice, -6, 3);
            $iNextSliced = substr($fPrice, 3, 6);
        }
        elseif($iLength >= 5) {
            $iSliced = substr($fPrice, -5, 2);
            $iNextSliced = substr($fPrice, 2, 5);
        }
        elseif($iLength >= 4) {
            $iSliced = substr($fPrice, -4, 1);
            $iNextSliced = substr($fPrice, 1, 4);
        }
        else {
            return false;
        }

        if($iSliced != 1){
            $this->_convertRouter($iSliced);
        }
        if($iSliced != 0){
            $this->_convertVariety($iSliced, 'thousands');
        }
        $this->_convertRouter($iNextSliced);
        return true;
    }//end of _convertThousands() method.
    
// --------------------------------------------------------------------
    
    /**
     * Metoda _convertHundreds();
     * 
     * Metoda wycina pierwszy znak liczby, ktora jest setka
     * I wstawia go jako index tablicy skladowych "hundreds"
     * Przyklad: 200 - 2 - hundreds[2] - dwiescie
     * Nastepnie sprawdzane sa kolejne znaki przez substr
     * 
     * @access    protected
     * @param    integer    $fPrice - Kwota
     * @return    boolean
     */
    protected function _convertHundreds($fPrice)
    {
        $iIndex = substr($fPrice, -3, 1);
        $this->_aOutput[] = $this->_aComponents['hundreds'][$iIndex];
            if(substr($fPrice, 1, 2) > 0){
                $this->_convertRouter(substr($fPrice, 1, 2));
            }
            else {
                $this->_convertTens(substr($fPrice, 1, 2));
            }
        return true;
    }//end of _convertHundreds() method.
    
// --------------------------------------------------------------------
    
    /**
     * Metoda _convertTens();
     * 
     * Metoda sprawdza czy podanej kwoty nie mozna dopasowac do jednostek
     * Jezeli nie mozna, wycinany jest pierwszy znak kwoty
     * I wstawiany jest jako index tablicy skladowych "tens"
     * Kolejny znak jest wysylany znowu do routera
     * 
     * @access    protected
     * @param    integer    $fPrice - Kwota
     * @return    boolean
     */
    protected function _convertTens($fPrice)
    {
        if(array_key_exists((string)$fPrice, $this->_aComponents['unities']) && substr($fPrice, 1, 2) != 0){
            $this->_aOutput[] = $this->_aComponents['unities'][$fPrice];
            return true;
        }
        $iIndex = substr($fPrice, 0, 1);
        $this->_aOutput[] = $this->_aComponents['tens'][$iIndex];
        if(substr($fPrice, 1, 2) != 0){
            $this->_convertRouter(substr($fPrice, 1, 2));
        }
        return true;
    }//end of _convertTens() method.
    
// --------------------------------------------------------------------
    
    /**
     * Metoda _convertUnities();
     * 
     * Metoda wstawia otrzymana liczbe jako index tablicy "unities"
     * Dzieki temu wiadomo na jakie slowo zamienic dana liczbe
     * 1 - unities[1] - jeden, 2 - unities[2] - dwa itd
     * 
     * @access    protected
     * @param    integer    $fPrice    - Kwota
     * @return    boolean
     */
    protected function _convertUnities($fPrice)
    {
        $this->_aOutput[] = $this->_aComponents['unities'][$fPrice];
        return true;
    }//end of _convertUnities() method.
    
// --------------------------------------------------------------------
    
    /**
     * Metoda _convertVariety();
     * 
     * Metoda na podstawie otrzymanego typu i kwoty
     * Decyduje o prawidlowej polskiej odmianie
     * Typ jest niczym innym jak indexem tablicy skladowych
     * Przyklad: currency, thousands, bilions, milions
     * 
     * @access    protected
     * @param    integer    $fPrice    - Kwota
     * @param    boolean    $sType    - Typ
     * @return    boolean
     */
    protected function _convertVariety($fPrice, $sType)
    {
        if($fPrice > 9){
            $iLastIntegers = substr($fPrice, -2);
            $sOneCurrency = $this->_aComponents[$sType][2];
        }
        else {
            $iLastIntegers = substr($fPrice, -1);
            $sOneCurrency = $this->_aComponents[$sType][0];
        }

        if($iLastIntegers >= 15){
            $iLastIntegers = substr($iLastIntegers, 1, 2);
        }
        
        if($iLastIntegers >= 11){
            $this->_aOutput[] = $this->_aComponents[$sType][2];
        }
        elseif($iLastIntegers == 0){
            $this->_aOutput[] = $this->_aComponents[$sType][2];
        }
        elseif($iLastIntegers == 1){
            $this->_aOutput[] = $sOneCurrency;
        }
        elseif($iLastIntegers >= 5){
            $this->_aOutput[] = $this->_aComponents[$sType][2];
        }
        elseif($iLastIntegers >= 2){
            $this->_aOutput[] = $this->_aComponents[$sType][1];
        }
        return true;
    }//end of _convertVariety() method
    
}//end of KwotaSlownie Library
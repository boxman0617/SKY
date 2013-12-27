<?php
class PayPalButton
{
    private $__merchaintID;
    
    public function __construct($merchID)
    {
        $this->__merchaintID = $merchID;
    }
    
    public function Create($text = 'Buy Now')
    {
        $h = new HTML();
        $form = $h->form(
            $h->input(array(
                'type'  => 'hidden',
                'name'  => 'cmd',
                'value' => '_xclick'
            )).
            $h->input(array(
                'type'  => 'hidden',
                'name'  => 'business',
                'value' => $this->__merchaintID
            )).
            $h->input(array(
                'type'  => 'hidden',
                'name'  => 'lc',
                'value' => 'US'
            )).
            $h->input(array(
                'type'  => 'hidden',
                'name'  => 'currency_code',
                'value' => 'USD'
            )).
            $h->input(array(
                'type'  => 'hidden',
                'name'  => 'button_subtype',
                'value' => 'services'
            )).
            $h->input(array(
                'type'  => 'hidden',
                'name'  => 'no_note',
                'value' => '1'
            )).
            $h->input(array(
                'type'  => 'hidden',
                'name'  => 'no_shipping',
                'value' => '2'
            )).
            $h->input(array(
                'type'  => 'hidden',
                'name'  => 'rm',
                'value' => '1'
            )).
            $h->input(array(
                'type'  => 'hidden',
                'name'  => 'return',
                'value' => 'http://facesforpebble.com/app/'.$this->id.'/order/success'
            )).
            $h->input(array(
                'type'  => 'hidden',
                'name'  => 'cancel_return',
                'value' => 'http://facesforpebble.com/app/'.$this->id.'/order/cancel'
            )).
            $h->input(array(
                'type'  => 'hidden',
                'name'  => 'item_name',
                'value' => $this->name
            )).
            $h->input(array(
                'type'  => 'hidden',
                'name'  => 'item_number',
                'value' => $this->id
            )).
            $h->input(array(
                'type'  => 'hidden',
                'name'  => 'amount',
                'value' => $this->get_raw('price')
            )).
            $h->input(array(
                'type'  => 'submit',
                'name'  => 'submit',
                'value' => $text
            )).
            $h->img(array(
                'alt'   => '',
                'border'=> '0',
                'src'   => 'https://www.paypalobjects.com/en_US/i/scr/pixel.gif',
                'width' => '1',
                'height'=> '1'
            )), 
            array(
            'target'    => '_top',
            'action'    => 'https://www.paypal.com/cgi-bin/webscr',
            'method'    => 'POST'
        ));
        return $form;
    }
}
?>
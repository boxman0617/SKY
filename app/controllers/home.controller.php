<?php
class Home extends Controller
{
    protected $before_filter = array(
        'BeforeIndex' => array(
            'only' => 'Index'
        )
    );
    protected $after_filter = array(
        'AfterIndex' => array(
            'only' => 'Index'
        )
    );
    
    public function AfterIndex()
    {
        $this->Assign('after', 'AFTER RAN!');
    }
    
    public function BeforeIndex()
    {
        //switch(rand(0, 3))
        //{
        //    case 0:
        //        $this->SetFlash('This is an info notification');
        //        $this->Assign('flash_type', 'info');
        //        break;
        //    case 1:
        //        $this->SetFlash('This is a warning notification');
        //        $this->Assign('flash_type', 'warning');
        //        break;
        //    case 2:
        //        $this->SetFlash('This is a success notification');
        //        $this->Assign('flash_type', 'success');
        //        break;
        //    case 3:
        //        $this->SetFlash('This is an error notification');
        //        $this->Assign('flash_type', 'error');
        //        break;
        //}
    }
    
    public function Index()
    {
        $this->Assign('test', 'Heading 1');
    }
    
    public function Show($id)
    {
        $this->Assign('id', $id);
        $mailer = new HomeMailer(array(
            'article' => $id,
            'name' => 'Alan'
        ));
        $mailer->Deliver('TestEmail');
    }
    
    public function Test($status)
    {
        $this->Render(array(
            'action' => 'Show',
            'params' => $status
            )
        );
    }
    
    public function Edit($id)
    {
        //// Test using model zip [Requires zip table]
        //$zip = new Zip();
        //$r = $zip->find_by_zip('92234');
        //foreach($r as $z)
        //{
        //    $this->Assign('city', $z->city);
        //}
        $this->Assign('city', 'MENIFEE');
        $this->Assign('id', $id);
    }
    
    public function Update($id)
    {
        // Do stuff with $_POST
        $this->RedirectTo(array('action' => 'Show', 'params' => $id));
    }
}
?>
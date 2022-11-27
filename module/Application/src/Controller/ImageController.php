<?php
namespace Application\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Application\Form\ImageForm;

class ImageController extends AbstractActionController 
{
    private $imageManager;
    

    public function __construct($imageManager)
    {
        $this->imageManager = $imageManager;
    }

    public function indexAction() 
    {
        $files = $this->imageManager->getSavedFiles();
        
        return new ViewModel([
            'files'=>$files
        ]);
    }
    
    public function uploadAction() 
    {
        $form = new ImageForm();
        
        if($this->getRequest()->isPost()) {
            
            $request = $this->getRequest();
            $data = array_merge_recursive(
                $request->getPost()->toArray(),
                $request->getFiles()->toArray()
            );
            
            $form->setData($data);
            
            if($form->isValid()) {
                $data = $form->getData();

                return $this->redirect()->toRoute('images', ['action'=>'index']);
            }                        
        } 
        return new ViewModel([
            'form' => $form
        ]);
    }

    public function fileAction() 
    {
        $fileName = $this->params()->fromQuery('name', '');

        if (empty($fileName) || strlen($fileName)>128) {
            throw new \Exception('File name is empty or too long');
        }

        $fileName = $this->imageManager->getImagePathByName($fileName);
                
        $fileInfo = $this->imageManager->getImageFileInfo($fileName);        
        if ($fileInfo===false) {
            $this->getResponse()->setStatusCode(404);            
            return;
        }
        $response = $this->getResponse();
        $headers = $response->getHeaders();
        $headers->addHeaderLine("Content-type: " . $fileInfo['type']);        
        $headers->addHeaderLine("Content-length: " . $fileInfo['size']);
               
        $fileContent = $this->imageManager->getImageFileContent($fileName);
        if($fileContent!==false) {                
            $response->setContent($fileContent);
        } else {        
            $this->getResponse()->setStatusCode(500);
            return;
        }
        
        return $this->getResponse();
    }   
    
    public function deleteAction()
    {
        $query = $this->params()->fromQuery('name', '');
        $fileName = $this->imageManager->getImagePathByName($query);

        if(file_exists($fileName)) unlink($fileName);
        else throw new \Exception('File doesn´t exist');
        return $this->redirect()->toRoute('images', ['action'=>'index']);
    }
}
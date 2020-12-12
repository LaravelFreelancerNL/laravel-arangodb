<?php

namespace LaravelFreelancerNL\Aranguent\Concerns;

use ArangoDBClient\CollectionHandler as ArangoCollectionHandler;
use ArangoDBClient\DocumentHandler as ArangoDocumentHandler;
use ArangoDBClient\GraphHandler as ArangoGraphHandler;
use ArangoDBClient\UserHandler as ArangoUserHandler;
use ArangoDBClient\ViewHandler as ArangoViewHandler;
use LaravelFreelancerNL\Aranguent\Document;
use Throwable;

trait HandlesArangoDb
{

    protected $arangoHandlers;

    public function getCollectionHandler()
    {
        if (!isset($this->arangoHandlers['collection'])) {
            $this->arangoHandlers['collection'] = new ArangoCollectionHandler($this->arangoConnection);
            $this->arangoHandlers['collection']->setDocumentClass(Document::class);
        }

        return $this->arangoHandlers['collection'];
    }

    public function getDocumentHandler()
    {
        if (!isset($this->arangoHandlers['document'])) {
            $this->arangoHandlers['document'] = new ArangoDocumentHandler($this->arangoConnection);
            $this->arangoHandlers['document']->setDocumentClass(Document::class);
        }

        return $this->arangoHandlers['document'];
    }

    public function getUserHandler()
    {
        if (!isset($this->arangoHandlers['user'])) {
            $this->arangoHandlers['user'] = new ArangoUserHandler($this->arangoConnection);
            $this->arangoHandlers['user']->setDocumentClass(Document::class);
        }

        return $this->arangoHandlers['user'];
    }

    public function getGraphHandler()
    {
        if (!isset($this->arangoHandlers['graph'])) {
            $this->arangoHandlers['graph'] = new ArangoGraphHandler($this->arangoConnection);
            $this->arangoHandlers['graph']->setDocumentClass(Document::class);
        }

        return $this->arangoHandlers['graph'];
    }

    public function getViewHandler()
    {
        if (!isset($this->arangoHandlers['view'])) {
            $this->arangoHandlers['view'] = new ArangoViewHandler($this->arangoConnection);
            $this->arangoHandlers['view']->setDocumentClass(Document::class);
        }

        return $this->arangoHandlers['view'];
    }
}

<?php

namespace Kubernetes\Client\Adapter\Http\Repository;

use Kubernetes\Client\Adapter\Http\HttpAdapter;
use Kubernetes\Client\Adapter\Http\HttpConnector;
use Kubernetes\Client\Exception\ClientError;
use Kubernetes\Client\Exception\NamespaceNotFound;
use Kubernetes\Client\Model\KeyValueObjectList;
use Kubernetes\Client\Model\KubernetesNamespace;
use Kubernetes\Client\Model\NamespaceList;
use Kubernetes\Client\Repository\NamespaceRepository;

class HttpNamespaceRepository implements NamespaceRepository
{
    /**
     * @var HttpConnector
     */
    private $connector;

    /**
     * @param HttpConnector $connector
     */
    public function __construct(HttpConnector $connector)
    {
        $this->connector = $connector;
    }

    /**
     * {@inheritdoc}
     */
    public function findAll()
    {
        return $this->connector->get('/namespaces', [
            'class' => NamespaceList::class,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function findByLabels(KeyValueObjectList $labels)
    {
        return $this->connector->get('/namespaces?'.http_build_query([
            'labelSelector' => HttpAdapter::createLabelSelector($labels->toAssociativeArray()),
        ]), [
            'class' => NamespaceList::class,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function create(KubernetesNamespace $namespace)
    {
        return $this->connector->post('/namespaces', $namespace, [
            'class' => KubernetesNamespace::class,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function findOneByName($name)
    {
        try {
            return $this->connector->get('/namespaces/'.$name, [
                'class' => KubernetesNamespace::class,
            ]);
        } catch (ClientError $e) {
            if ($e->getStatus()->getCode() === 404) {
                throw new NamespaceNotFound(sprintf(
                    'Namespace named "%s" is not found',
                    $name
                ));
            }

            throw $e;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function delete(KubernetesNamespace $namespace)
    {
        return $this->connector->delete(sprintf('/namespaces/%s', $namespace->getMetadata()->getName()), null, [
            'class' => KubernetesNamespace::class,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function exists($name)
    {
        try {
            $this->findOneByName($name);
        } catch (NamespaceNotFound $e) {
            return false;
        }

        return true;
    }
}

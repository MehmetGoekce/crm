<?php

namespace OroCRM\Bundle\ContactBundle\Controller\Api\Rest;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Response;

use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Routing\ClassResourceInterface;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Oro\Bundle\AddressBundle\Entity\AddressType;

use Oro\Bundle\UserBundle\Annotation\Acl;
use Oro\Bundle\UserBundle\Annotation\AclAncestor;

use Oro\Bundle\SoapBundle\Form\Handler\ApiFormHandler;
use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestController;
use Oro\Bundle\SoapBundle\Entity\Manager\ApiFlexibleEntityManager;

use OroCRM\Bundle\ContactBundle\Entity\Contact;
use OroCRM\Bundle\ContactBundle\Entity\ContactAddress;

/**
 * @RouteResource("contact")
 * @NamePrefix("oro_api_")
 */
class ContactController extends RestController implements ClassResourceInterface
{
    /**
     * REST GET list
     *
     * @QueryParam(
     *     name="page", requirements="\d+", nullable=true, description="Page number, starting from 1. Defaults to 1."
     * )
     * @QueryParam(
     *     name="limit", requirements="\d+", nullable=true, description="Number of items per page. defaults to 10."
     * )
     * @ApiDoc(
     *      description="Get all contacts items",
     *      resource=true
     * )
     * @AclAncestor("orocrm_contact_list")
     * @return Response
     */
    public function cgetAction()
    {
        $page = (int)$this->getRequest()->get('page', 1);
        $limit = (int)$this->getRequest()->get('limit', self::ITEMS_PER_PAGE);

        return $this->handleGetListRequest($page, $limit);
    }

    /**
     * REST GET item
     *
     * @param string $id
     *
     * @ApiDoc(
     *      description="Get contact item",
     *      resource=true
     * )
     * @AclAncestor("orocrm_contact_view")
     * @return Response
     */
    public function getAction($id)
    {
        return $this->handleGetRequest($id);
    }

    /**
     * REST PUT
     *
     * @param int $id Contact item id
     *
     * @ApiDoc(
     *      description="Update contact",
     *      resource=true
     * )
     * @AclAncestor("orocrm_contact_update")
     * @return Response
     */
    public function putAction($id)
    {
        return $this->handleUpdateRequest($id);
    }

    /**
     * Create new contact
     *
     * @ApiDoc(
     *      description="Create new contact",
     *      resource=true
     * )
     * @AclAncestor("orocrm_contact_create")
     */
    public function postAction()
    {
        return $this->handleCreateRequest();
    }

    /**
     * REST DELETE
     *
     * @param int $id
     *
     * @ApiDoc(
     *      description="Delete Contact",
     *      resource=true
     * )
     * @Acl(
     *      id="orocrm_contact_delete",
     *      name="Delete contact",
     *      description="Delete contact",
     *      parent="orocrm_contact"
     * )
     * @return Response
     */
    public function deleteAction($id)
    {
        return $this->handleDeleteRequest($id);
    }

    /**
     * Get entity Manager
     *
     * @return ApiFlexibleEntityManager
     */
    public function getManager()
    {
        return $this->get('orocrm_contact.contact.manager.api');
    }

    /**
     * @return FormInterface
     */
    public function getForm()
    {
        return $this->get('orocrm_contact.form.contact.api');
    }

    /**
     * @return ApiFormHandler
     */
    public function getFormHandler()
    {
        return $this->get('orocrm_contact.form.handler.contact.api');
    }

    /**
     * {@inheritDoc}
     */
    protected function getPreparedItem($entity)
    {
        /** @var Contact $entity */
        // basic result
        $result = parent::getPreparedItem($entity);

        // use contact code instead of label
        $result['source'] = $entity->getSource()->getName();

        // set assigned to user data
        $assignedTo = $entity->getAssignedTo();
        if ($assignedTo) {
            $result['assignedTo'] = $assignedTo->getId();
        } else {
            $result['assignedTo'] = null;
        }

        // set reports to contact data
        $reportsTo = $entity->getReportsTo();
        if ($reportsTo) {
            $result['reportsTo'] = $reportsTo->getId();
        } else {
            $result['reportsTo'] = null;
        }

        // set contact group data
        $groupsData = array();
        foreach ($entity->getGroups() as $group) {
            $groupsData[] = parent::getPreparedItem($group);
        }
        $result['groups'] = $groupsData;

        // convert addresses to plain array
        $addressData = array();
        /** @var $entity Contact */
        foreach ($entity->getAddresses() as $address) {
            $addressArray = parent::getPreparedItem($address);
            $addressArray['types'] = $address->getTypeNames();
            $addressArray = $this->removeUnusedValues($addressArray, array('owner'));
            $addressData[] = $addressArray;
        }
        $result['addresses'] = $addressData;

        // convert accounts to plain array
        $accountsIds = array();
        foreach ($entity->getAccounts() as $account) {
            $accountsIds[] = $account->getId();
        }
        $result['accounts'] = $accountsIds;

        return $result;
    }

    /**
     * @param array $data
     * @param array $unusedKeys
     * @return array
     */
    protected function removeUnusedValues(array $data, array $unusedKeys)
    {
        foreach ($unusedKeys as $key) {
            if (array_key_exists($key, $data)) {
                unset($data[$key]);
            }
        }

        return $data;
    }
}

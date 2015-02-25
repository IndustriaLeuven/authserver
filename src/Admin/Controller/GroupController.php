<?php

namespace Admin\Controller;

use Admin\Controller\Traits\Routes\LinkUnlinkTrait;
use App\Entity\Group;
use FOS\RestBundle\Controller\Annotations\Get;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use vierbergenlars\Bundle\RadRestBundle\View\View;
use FOS\RestBundle\Controller\Annotations\View as AView;

class GroupController extends DefaultController
{
    use LinkUnlinkTrait;

    protected function handleLink($type, $parent, $link)
    {
        switch ($type) {
            case 'group':
                $group = $link->getData();
                if (!$group instanceof Group) {
                    throw new BadRequestHttpException('Subresource of wrong type (expected: group)');
                }
                if (!$parent->getGroups()->contains($group)) {
                    $parent->addGroup($group);
                }
                break;
            default:
                throw new BadRequestHttpException('Invalid relationship (allowed: group)');
        }
    }

    protected function handleUnlink($type, $parent, $link)
    {
        switch ($type) {
            case 'group':
                $group = $link->getData();
                if (!$group instanceof Group) {
                    throw new BadRequestHttpException('Subresource of wrong type (expected: group)');
                }
                $parent->removeGroup($group);
                break;
            default:
                throw new BadRequestHttpException('Invalid relationship (allowed: group)');
        }
    }

    protected function getBatchActions()
    {
        $actions = parent::getBatchActions();
        $actions['Exportable']['PATCH_exportable_true'] = 'Enable';
        $actions['Exportable']['PATCH_exportable_false'] = 'Disable';
        $actions['Member types']['PATCH_noUsers_false'] = 'Allow users';
        $actions['Member types']['PATCH_noUsers_true'] = 'Deny users';
        $actions['Member types']['PATCH_noGroups_false'] = 'Allow groups';
        $actions['Member types']['PATCH_noGroups_true'] = 'Deny groups';

        return $actions;
    }

    /**
     * @Get(path="/{id}/members")
     * @AView
     */
    public function getMembersAction(Request $request, $id)
    {
        $group = $this->getAction($id)->getData();
        /* @var $group Group */
        $view  = View::create($this->getPaginator()->paginate($group->getMembers(), $request->query->get('page', 1)));
        $view->setExtraData(array(
            'group' => $group,
        ));
        $view->getSerializationContext()->setGroups($this->getSerializationGroups('get_members'));

        return $this->handleView($view);
    }

    public function getSerializationGroups($action)
    {
        switch($action) {
            case 'get_members':
                return array(
                    'admin_group_list_members',
                    'list',
                );
            default:
                return parent::getSerializationGroups($action);
        }
    }
}

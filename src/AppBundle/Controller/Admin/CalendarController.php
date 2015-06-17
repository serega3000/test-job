<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Controller\Admin;

use AppBundle\Form\EventType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use AppBundle\Entity\Event;

/**
 * Контроллер для управления календарем
 */
class CalendarController extends Controller
{
    /**
     * @Route("/", name="admin_index")
     * @Route("/", name="admin_event_index")
     * @Method("GET")
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();
        $events = $em->getRepository('AppBundle:Event')->findAll();

        return $this->render('admin/event/index.html.twig', array('events' => $events));
    }

    /**
     * Creates a new Event entity.
     *
     * @Route("/new", name="admin_event_new")
     * @Method({"GET", "POST"})
     *
     */
    public function newAction(Request $request)
    {
        $event = new Event();
        $event->setAuthorEmail($this->getUser()->getEmail());
        $form = $this->createForm(new EventType(), $event);

        $form->handleRequest($request);

        // the isSubmitted() method is completely optional because the other
        // isValid() method already checks whether the form is submitted.
        // However, we explicitly add it to improve code readability.
        // See http://symfony.com/doc/current/best_practices/forms.html#handling-form-submits
        if ($form->isSubmitted() && $form->isValid()) {

            $em = $this->getDoctrine()->getManager();
            $em->persist($event);
            $em->flush();

            return $this->redirectToRoute('admin_event_index');
        }

        return $this->render('admin/event/new.html.twig', array(
            'event' => $event,
            'form' => $form->createView(),
        ));
    }

    /**
     * Finds and displays a Event entity.
     *
     * @Route("/{id}", requirements={"id" = "\d+"}, name="admin_event_show")
     * @Method("GET")
     *
     * NOTE: You can also centralize security logic by using a "voter"
     * See http://symfony.com/doc/current/cookbook/security/voters_data_permission.html
     */
    public function showAction(Event $event)
    {
        $deleteForm = $this->createDeleteForm($event);

        return $this->render('admin/event/show.html.twig', array(
            'event'        => $event,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing Event entity.
     *
     * @Route("/{id}/edit", requirements={"id" = "\d+"}, name="admin_event_edit")
     * @Method({"GET", "POST"})
     * @Security("user.isBoss")
     */
    public function editAction(Event $event, Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $editForm = $this->createForm(new EventType(), $event);
        $deleteForm = $this->createDeleteForm($event);

        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $event->setSlug($this->get('slugger')->slugify($event->getTitle()));
            $em->flush();

            return $this->redirectToRoute('admin_event_edit', array('id' => $event->getId()));
        }

        return $this->render('admin/event/edit.html.twig', array(
            'event'        => $event,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Deletes a Event entity.
     *
     * @Route("/{id}", name="admin_event_delete")
     * @Method("DELETE")
     * @Security("user.isBoss()")
     *
     * The Security annotation value is an expression (if it evaluates to false,
     * the authorization mechanism will prevent the user accessing this resource).
     * The isAuthor() method is defined in the AppBundle\Entity\Event entity.
     */
    public function deleteAction(Request $request, Event $event)
    {
        $form = $this->createDeleteForm($event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $em->remove($event);
            $em->flush();
        }

        return $this->redirectToRoute('admin_event_index');
    }

    /**
     * Creates a form to delete a Event entity by id.
     *
     * This is necessary because browsers don't support HTTP methods different
     * from GET and POST. Since the controller that removes the event events expects
     * a DELETE method, the trick is to create a simple form that *fakes* the
     * HTTP DELETE method.
     * See http://symfony.com/doc/current/cookbook/routing/method_parameters.html.
     *
     * @param Event $event The event object
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(Event $event)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('admin_event_delete', array('id' => $event->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }
}

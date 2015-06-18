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
 * @Route("/admin/event")
 * @Security("has_role('ROLE_ADMIN')")
 * 
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

        return $this->render('admin/event/index.html.twig', array('events' => $events, 'calendar_class' => $this->getUser()->isBoss() ? "boss" : "no-boss"));
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
        
        if(isset($_GET['date']))
        {
            $date_arr = explode("-", $_GET['date']);
            $defaultTime = new \DateTime();
            $defaultTime->setDate($date_arr[0], $date_arr[1], $date_arr[2]);            
            $event->setStartTime($defaultTime);   
        }
        
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
     * @Security("user.isBoss()")
     */
    public function editAction(Event $event, Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $editForm = $this->createForm(new EventType(), $event);
        $deleteForm = $this->createDeleteForm($event);

        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
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
     * @Route("/data", name="admin_event_data")
     * @Method("GET")
     */
    public function dataAction()
    {
        $em = $this->getDoctrine()->getManager();
        $events = $em->getRepository('AppBundle:Event')->findAll();
        
        $returnValue = array();
        
        foreach($events as $event)
        {
            $time = strtotime($event->getStartTime()->format('r'));            
            /* @var $event Event */
            $returnValue[] = array(
                'id' => $event->getId(),
                'title' => $event->getTitle(),
                'url' => "/admin/event/".$event->getId(),
                'class' => 'event-info',
                'start' => $time."000",
                'end' => ($time + 100)."000"
            );
        }
        
        $data = json_encode( array('success'=>1,'result'=>$returnValue) );
        $headers = array( 'Content-type' => 'application-json; charset=utf8' );
        $response = new \Symfony\Component\HttpFoundation\Response( $data, 200, $headers );
        return $response;        
    }
 
    /**
     * @Route("/update_date/{id}", name="admin_event_update_date")
     * @Method("POST")
     * @Security("user.isBoss()")
     */    
    public function updateDateAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $event = $em->getRepository('AppBundle:Event')->find($id);        
        
        $new_date = $_POST['date'];
        $date_arr = explode("-", $new_date);
        
        $time = new \DateTime($event->getStartTime()->format('r'));
        $time->setDate($date_arr[0], $date_arr[1], $date_arr[2]);
        
        $event->setStartTime($time);        
        
        $em->flush();
        
        $time1 = strtotime($event->getStartTime()->format('r'));                 
        
        $returnValue = array(
            'success' => 1,
            'data' => array(
                'id' => $event->getId(),
                'title' => $event->getTitle(),
                'url' => "/admin/event/".$event->getId(),
                'class' => 'event-info',
                'start' => $time1."000",
                'end' => ($time1 + 100)."000"                
            )            
        );        
        
        $data = json_encode($returnValue);
        $headers = array( 'Content-type' => 'application-json; charset=utf8' );
        $response = new \Symfony\Component\HttpFoundation\Response( $data, 200, $headers );
        return $response;            
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

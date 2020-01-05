<?php

namespace App\EventSubscriber;

use App\Repository\ConferenceRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Twig\Environment;

class TwigEventSubscriber implements EventSubscriberInterface
{
    /**
     * Description $twig field
     *
     * @var Environment $twig
     */
    protected $twig;
    /**
     * Description $conferenceRepository field
     *
     * @var ConferenceRepository $conferenceRepository
     */
    protected $conferenceRepository;

    /**
     * TwigEventSubscriber constructor
     *
     * @param Environment          $twig
     * @param ConferenceRepository $conferenceRepository
     */
    public function __construct(
        Environment $twig,
        ConferenceRepository $conferenceRepository
    ) {
        $this->twig = $twig;
        $this->conferenceRepository = $conferenceRepository;
    }

    public function onControllerEvent(ControllerEvent $event): void
    {
        $this->twig->addGlobal('conferences', $this->conferenceRepository->findAll());
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ControllerEvent::class => 'onControllerEvent',
        ];
    }
}

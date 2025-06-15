<?php

namespace App\Controller;

use ErrorException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class NewsFeedController extends AbstractController
{
    #[Route('/newsfeed', name: 'newsfeed')]
    public function newsFeed(): Response
    {
        $rssNewsFeed = 'https://secure.runescape.com/m=news/latest_news.rss';

        try {
            $feed = simplexml_load_file($rssNewsFeed);
        } catch (ErrorException $exception) { // Catching ErrorException to handle issues with loading the RSS feed
            $this->addFlash(
                'danger',
                'Unable to load the news feed. This is likely due to a temporary issue with the RSS feed.' .
                ' Please try again later.'
            );
            return $this->redirectToRoute('summary');
        }

        return $this->render('newsfeed.html.twig', [
            'rss' => $feed,
        ]);
    }
}

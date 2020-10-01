<?php

namespace App\DataPersister;

use App\Entity\Tag;
use App\Entity\Article;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\String\Slugger\SluggerInterface;
use ApiPlatform\Core\DataPersister\ContextAwareDataPersisterInterface;

class ArticleDataPersister implements ContextAwareDataPersisterInterface {
    
    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @param SluggerInterface
     */
    private $slugger;

    /**
     * @param Request
     */
    private $request;

    public function __construct(EntityManagerInterface $em, SluggerInterface $slugger, RequestStack $request)
    {
        $this->em = $em;
        $this->slugger = $slugger;
        $this->request = $request->getCurrentRequest();
    }

    public function supports($data, array $context = []): bool
    {
        return $data instanceof Article;
    }

    public function persist($data, array $context = [])
    {
        // Update le slug seulement si l'article n'est pas publié
        $data->setSlug(
            $this
                ->slugger
                ->slug(strtolower($data->getTitle())). '-' .uniqid()
        );

        // Affecter une value à updatedAt si ce n'est pas une request POST
        if ($this->request->getMethod() !== 'POST') {
            $data->setUpdatedAt(new \DateTime());
        }
        $tagRepository = $this->em->getRepository(Tag::class);
        foreach($data->getTags() as $tag) {
            $t = $tagRepository->findOneByLabel($tag->getLabel());

            // Si le tag existe on ne le persiste pas
            if ($t !== null) {
                $data->removeTag($tag);
                $data->addTag($t);
            } else {
                $this->em->persist($tag);
            }
        }

        $this->em->persist($data);
        $this->em->flush();
    }

    public function remove($data, array $context = [])
    {
        $this->em->remove($data);
        $this->em->flush();
    }

}
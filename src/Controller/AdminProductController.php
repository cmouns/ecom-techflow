<?php

namespace App\Controller;

use App\Entity\Product;
use App\Entity\ProductImage;
use App\Form\ProductType;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/admin/product', name: 'app_admin_product_')]
#[IsGranted('ROLE_ADMIN')]
final class AdminProductController extends AbstractController
{
    /**
     * Affiche la liste des produits du plus récent au plus ancien.
     */
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(ProductRepository $productRepository): Response
    {
        // Récupère tout pour que l'admin ait une vue d'ensemble
        $products = $productRepository->findBy([], ['createdAt' => 'DESC']);

        return $this->render('admin_product/index.html.twig', [
            'products' => $products,
        ]);
    }

    /**
     * Formulaire d'ajout d'un nouveau produit.
     */
    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $product = new Product();
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Génération du slug pour l'URL à partir du nom
            $slug = $slugger->slug($product->getName())->lower();
            $product->setSlug((string) $slug);

            // Gère l'upload des images avant de sauvegarder le produit
            $this->handleImageUpload($form, $product, $entityManager, $slugger);

            $entityManager->persist($product);
            $entityManager->flush();

            $this->addFlash('success', 'Le produit et sa galerie ont été ajoutés !');

            return $this->redirectToRoute('app_admin_product_index');
        }

        return $this->render('admin_product/new.html.twig', [
            'form' => $form,
        ]);
    }

    /**
     * Modification d'un produit et ajout possible de nouvelles photos.
     */
    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Product $product, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Met à jour le slug au cas où le nom du produit a changé
            $slug = $slugger->slug($product->getName())->lower();
            $product->setSlug((string) $slug);

            // Si de nouvelles images sont ajoutées via le formulaire
            $this->handleImageUpload($form, $product, $entityManager, $slugger);

            $entityManager->flush();

            $this->addFlash('success', 'Le produit a été mis à jour avec succès.');

            return $this->redirectToRoute('app_admin_product_index');
        }

        return $this->render('admin_product/edit.html.twig', [
            'product' => $product,
            'form' => $form,
        ]);
    }

    /**
     * Suppression complète d'un produit.
     */
    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, EntityManagerInterface $entityManager, Product $product): Response
    {
        // Vérification de sécurité pour éviter les suppressions par simple URL
        if ($this->isCsrfTokenValid('delete'.$product->getId(), (string) $request->request->get('_token'))) {
            $entityManager->remove($product);
            $entityManager->flush();
        }
        $this->addFlash('success', 'Le produit a été supprimé définitivement');

        return $this->redirectToRoute('app_admin_product_index');
    }

    /**
     * Traite l'upload multiple d'images.
     *
     * @param FormInterface<Product> $form
     */
    private function handleImageUpload(FormInterface $form, Product $product, EntityManagerInterface $entityManager, SluggerInterface $slugger): void
    {
        /** @var UploadedFile[] $imageFiles */
        $imageFiles = $form->get('images')->getData();
        $slug = $slugger->slug($product->getName())->lower();

        if ($imageFiles) {
            foreach ($imageFiles as $imageFile) {
                // Création d'un nom de fichier unique pour éviter les collisions
                $newFilename = $slug.'-'.uniqid().'.'.$imageFile->guessExtension();

                try {
                    // Déplace le fichier physique dans le dossier configuré
                    $imageFile->move($this->getParameter('products_images_directory'), $newFilename);

                    // Crée une nouvelle entité Image liée au produit
                    $productImage = new ProductImage();
                    $productImage->setFilename($newFilename);
                    $productImage->setProduct($product);

                    $entityManager->persist($productImage);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors du transfert d\'une image.');
                }
            }
        }
    }

    /**
     * Suppression d'une image spécifique dans la galerie.
     */
    #[Route('/image/{id}/delete', name: 'image_delete', methods: ['POST'])]
    public function deleteImage(Request $request, ProductImage $image, EntityManagerInterface $entityManager): Response
    {
        $productId = $image->getProduct()->getId();

        if ($this->isCsrfTokenValid('delete'.$image->getId(), (string) $request->request->get('_token'))) {
            $filename = $image->getFilename();
            $imagesDirectory = $this->getParameter('products_images_directory');
            $filePath = $imagesDirectory.'/'.$filename;

            // S'assure de supprimer le fichier sur le serveur pour ne pas stocker de photos inutiles
            if (file_exists($filePath)) {
                unlink($filePath);
            }

            $entityManager->remove($image);
            $entityManager->flush();

            $this->addFlash('success', 'La photo a été retirée de la galerie.');
        }

        return $this->redirectToRoute('app_admin_product_edit', ['id' => $productId]);
    }
}

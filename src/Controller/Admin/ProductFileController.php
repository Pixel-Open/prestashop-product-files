<?php

namespace Pixel\Module\ProductFiles\Controller\Admin;

include_once _PS_MODULE_DIR_ . 'pixel_product_files/pixel_product_files.php';

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMException;
use Language;
use Pixel\Module\ProductFiles\Entity\ProductFile;
use Pixel\Module\ProductFiles\Entity\ProductFileLang;
use pixel_product_files;
use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class ProductFileController extends FrameworkBundleAdminController
{
    /**
     * Save the file
     *
     * @param Request $request
     *
     * @return RedirectResponse
     * @throws ORMException
     */
    public function saveAction(Request $request): RedirectResponse
    {
        $referer = $request->headers->get('referer') . '#tab-hooks';
        if (!$referer) {
            return $this->redirectToRoute('admin_product_catalog');
        }

        $idProduct = $request->get('id_product');
        if (!$idProduct) {
            return $this->redirect($referer);
        }

        /** @var UploadedFile $file */
        foreach ($request->files as $file) {
            if (!$file) {
                continue;
            }
            if (!in_array($file->getClientOriginalExtension(), pixel_product_files::ALLOWED_EXTENSIONS ?? [])) {
                $this->addFlash(
                    'error',
                    $this->trans('File extension is not allowed', 'Modules.Pixelproductfiles.Admin')
                );
                continue;
            }
            $fileName = uniqid() . '_' . $file->getClientOriginalName();
            $fileDirectory = pixel_product_files::FILE_BASE_DIR;

            if (!is_dir($fileDirectory)) {
                mkdir($fileDirectory, 0777, true);
            }
            $file->move($fileDirectory, $fileName);

            /** @var EntityManager $entityManager */
            $entityManager = $this->get('doctrine.orm.entity_manager');

            $idLang = $request->get('id_lang') ? (int)$request->get('id_lang') : null;
            if ($request->get('all_languages')) {
                $idLang = null;
            }

            $idShop = $request->get('id_shop') ? (int)$request->get('id_shop') : null;
            if ($request->get('all_shops')) {
                $idShop = null;
            }

            $productFile = new ProductFile();
            $productFile
                ->setFile($fileName)
                ->setIdProduct($idProduct)
                ->setIdLang($idLang)
                ->setIdShop($idShop);
            $entityManager->persist($productFile);
            $entityManager->flush();

            $available = Language::getLanguages();
            foreach ($available as $language) {
                if ($productFile->getIdLang() && ($productFile->getIdLang() !== (int)$language['id_lang'])) {
                    continue;
                }
                $productFileLang = new ProductFileLang();
                $productFileLang
                    ->setIdFile($productFile->getId())
                    ->setIdLang((int)$language['id_lang'])
                    ->setTitle($request->get('title', ''))
                    ->setDescription($request->get('description', ''))
                    ->setPosition($request->get('position') ? (int)$request->get('position') : 0);
                $entityManager->persist($productFileLang);
                $entityManager->flush();
            }

            $this->addFlash(
                'success',
                $this->trans('The file has been added', 'Modules.Pixelproductfiles.Admin')
            );
        }

        return $this->redirect($referer);
    }

    /**
     * Delete the file
     *
     * @param Request $request
     * @return RedirectResponse
     * @throws ORMException
     */
    public function deleteAction(Request $request): RedirectResponse
    {
        $referer = $request->headers->get('referer') . '#tab-hooks';
        if (!$referer) {
            return $this->redirectToRoute('admin_product_catalog');
        }

        $idFile = $request->get('id_file');
        if (!$idFile) {
            return $this->redirect($referer);
        }

        /** @var EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');
        $repository = $entityManager->getRepository(ProductFile::class);

        /** @var ProductFile $productFile */
        $productFile = $repository->findOneBy(['id' => $idFile]);
        if (!$productFile) {
            $this->addFlash(
                'error',
                $this->trans('File does not exist', 'Modules.Pixelproductfiles.Admin')
            );
            return $this->redirect($referer);
        }

        $filepath = pixel_product_files::FILE_BASE_DIR . $productFile->getFile();
        if (is_file($filepath)) {
            @unlink($filepath);
        }
        $entityManager->remove($productFile);
        $entityManager->flush();

        $this->addFlash(
            'success',
            $this->trans('The file has been deleted', 'Modules.Pixelproductfiles.Admin')
        );

        return $this->redirect($referer);
    }
}

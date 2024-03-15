<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

use GuzzleHttp\Client;

use DeezerAPI\Search;

use App\Entity\Catalogue\Livre;
use App\Entity\Catalogue\Musique;
use App\Entity\Catalogue\Piste;

use Psr\Log\LoggerInterface;

class AppFixtures extends Fixture
{
	protected $logger;

	public function __construct(LoggerInterface $logger)
	{
		$this->logger = $logger;
	}

	public function load(ObjectManager $manager): void
	{
		if (count($manager->getRepository("App\Entity\Catalogue\Article")->findAll()) == 0) {
			$ebay = new Ebay($this->logger);
			//$ebay->setCategory('CDs');
			$keywords = 'Johnny Hallyday';

			$formattedResponse = $ebay->findItemsAdvanced($keywords, 180);
			file_put_contents("ebayResponse.xml", $formattedResponse);
			$xml = simplexml_load_string($formattedResponse);

			if ($xml !== false) {
				foreach ($xml->children() as $child_1) {
					if ($child_1->getName() === "item") {
						if ($ebay->getParentCategoryIdById($child_1->primaryCategory->categoryId) == $ebay->getParentCategoryIdByName("CDs")) {
							$entityMusique = new Musique();
							$entityMusique->setId((int) $child_1->itemId);
							$title = $ebay->getItemSpecific("Release Title", $child_1->itemId);
							if ($title == null) $title = $child_1->title;
							$entityMusique->setTitre($title);
							$artist = $ebay->getItemSpecific("Artist", $child_1->itemId);
							if ($artist == null) $artist = "";
							$entityMusique->setArtiste($artist);
							$entityMusique->setDateDeParution("");
							$entityMusique->setPrix((float) $child_1->sellingStatus->currentPrice);
							$entityMusique->setDisponibilite(1);
							$entityMusique->setImage($child_1->galleryURL);
							if (!isset($albums)) {
								$deezerSearch = new Search($keywords);
								$artistes = $deezerSearch->searchArtist();
								$albums = $deezerSearch->searchAlbumsByArtist($artistes[0]->getId());
							}
							$j = 0;
							$sortir = ($j == count($albums));
							$albumTrouve = false;
							while (!$sortir) {
								$titreDeezer = str_replace(" ", "", mb_strtolower($albums[$j]->title));
								$titreEbay = str_replace(" ", "", mb_strtolower($entityMusique->getTitre()));
								$titreDeezer = str_replace("-", "", $titreDeezer);
								$titreEbay = str_replace("-", "", $titreEbay);
								$albumTrouve = ($titreDeezer == $titreEbay);
								if (mb_strlen($titreEbay) > mb_strlen($titreDeezer))
									$albumTrouve = $albumTrouve || (mb_strpos($titreEbay, $titreDeezer) !== false);
								if (mb_strlen($titreDeezer) > mb_strlen($titreEbay))
									$albumTrouve = $albumTrouve || (mb_strpos($titreDeezer, $titreEbay) !== false);
								$j++;
								$sortir = $albumTrouve || ($j == count($albums));
							}
							if ($albumTrouve) {
								$tracks = $deezerSearch->searchTracksByAlbum($albums[$j - 1]->getId());
								foreach ($tracks as $track) {
									$entityPiste = new Piste();
									$entityPiste->setTitre($track->title);
									$entityPiste->setMp3($track->preview);
									$manager->persist($entityPiste);
									$manager->flush();
									$entityMusique->addPiste($entityPiste);
								}
							}
							$manager->persist($entityMusique);
							$manager->flush();
						}
					}
				}
			}
		}
	}
}

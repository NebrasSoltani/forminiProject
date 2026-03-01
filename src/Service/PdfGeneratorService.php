<?php

namespace App\Service;

use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class PdfGeneratorService
{
    private Dompdf $dompdf;
    private string $projectDir;

    public function __construct(ParameterBagInterface $parameterBag)
    {
        $this->projectDir = $parameterBag->get('kernel.project_dir');
        
        // Configuration de Dompdf
        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isPhpEnabled', true);
        $options->set('tempDir', $this->projectDir . '/var/cache/pdf');
        
        $this->dompdf = new Dompdf($options);
    }

    /**
     * Génère un PDF à partir d'un template Twig
     */
    public function generatePdfFromHtml(string $html, string $filename = 'document.pdf'): string
    {
        // Créer une nouvelle instance DomPDF pour éviter les conflits
        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isPhpEnabled', true);
        $options->set('tempDir', $this->projectDir . '/var/cache/pdf');
        
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        // Créer le répertoire des factures s'il n'existe pas
        $invoicesDir = $this->projectDir . '/public/invoices';
        if (!is_dir($invoicesDir)) {
            mkdir($invoicesDir, 0755, true);
        }

        $filePath = $invoicesDir . '/' . $filename;
        file_put_contents($filePath, $dompdf->output());

        return $filePath;
    }

    /**
     * Génère le contenu HTML pour une facture de commande
     */
    public function generateInvoiceHtml($commande, $user): string
    {
        $date = new \DateTime();
        
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <title>Facture #' . $commande->getId() . '</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    font-size: 12px;
                    line-height: 1.4;
                    color: #333;
                    margin: 0;
                    padding: 20px;
                }
                .header {
                    border-bottom: 2px solid #007bff;
                    padding-bottom: 20px;
                    margin-bottom: 30px;
                }
                .logo {
                    font-size: 24px;
                    font-weight: bold;
                    color: #007bff;
                    margin-bottom: 5px;
                }
                .company-info {
                    font-size: 11px;
                    color: #666;
                }
                .invoice-info {
                    text-align: right;
                    margin-top: 20px;
                }
                .invoice-number {
                    font-size: 18px;
                    font-weight: bold;
                    color: #007bff;
                }
                .customer-info {
                    margin: 30px 0;
                    padding: 15px;
                    background-color: #f8f9fa;
                    border-radius: 5px;
                }
                .items-table {
                    width: 100%;
                    border-collapse: collapse;
                    margin: 20px 0;
                }
                .items-table th {
                    background-color: #007bff;
                    color: white;
                    padding: 10px;
                    text-align: left;
                }
                .items-table td {
                    padding: 10px;
                    border-bottom: 1px solid #ddd;
                }
                .items-table .total-row {
                    font-weight: bold;
                    background-color: #f8f9fa;
                }
                .footer {
                    margin-top: 50px;
                    padding-top: 20px;
                    border-top: 1px solid #ddd;
                    text-align: center;
                    font-size: 11px;
                    color: #666;
                }
                .watermark {
                    position: fixed;
                    top: 50%;
                    left: 50%;
                    transform: translate(-50%, -50%) rotate(-45deg);
                    font-size: 100px;
                    color: rgba(0, 123, 255, 0.1);
                    font-weight: bold;
                    z-index: -1;
                }
            </style>
        </head>
        <body>
            <div class="watermark">PAYÉ</div>
            
            <div class="header">
                <div class="logo">Formini.tn</div>
                <div class="company-info">
                    Plateforme e-learning Tunisienne<br>
                    Email: contact@formini.tn<br>
                    Téléphone: +216 XX XXX XXX
                </div>
                <div class="invoice-info">
                    <div class="invoice-number">FACTURE #' . str_pad($commande->getId(), 6, '0', STR_PAD_LEFT) . '</div>
                    <div>Date: ' . $date->format('d/m/Y') . '</div>
                    <div>Statut: PAYÉ</div>
                </div>
            </div>

            <div class="customer-info">
                <h3>Informations Client</h3>
                <strong>' . htmlspecialchars($user->getPrenom() . ' ' . $user->getNom()) . '</strong><br>
                Email: ' . htmlspecialchars($user->getEmail()) . '<br>
                ' . ($user->getTelephone() ? 'Téléphone: ' . htmlspecialchars($user->getTelephone()) . '<br>' : '') . '
                ' . ($commande->getAdresseLivraison() ? 'Adresse: ' . htmlspecialchars($commande->getAdresseLivraison()) . '<br>' : '') . '
            </div>

            <h3>Détails de la Commande</h3>
            <table class="items-table">
                <thead>
                    <tr>
                        <th>Produit</th>
                        <th>Quantité</th>
                        <th>Prix Unitaire</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>';

        foreach ($commande->getItems() as $item) {
            $html .= '
                    <tr>
                        <td>' . htmlspecialchars($item->getNomProduit()) . '</td>
                        <td>' . $item->getQuantite() . '</td>
                        <td>' . number_format($item->getPrixUnitaire(), 2, ',', ' ') . ' TND</td>
                        <td>' . number_format($item->getPrixUnitaire() * $item->getQuantite(), 2, ',', ' ') . ' TND</td>
                    </tr>';
        }

        $html .= '
                    <tr class="total-row">
                        <td colspan="3"><strong>Total TTC</strong></td>
                        <td><strong>' . number_format($commande->getTotal(), 2, ',', ' ') . ' TND</strong></td>
                    </tr>
                </tbody>
            </table>

            <div class="footer">
                <p>Merci pour votre confiance !</p>
                <p>Cette facture est générée automatiquement par Formini.tn</p>
                <p>Tous les prix sont en Dinars Tunisiens (TND)</p>
            </div>
        </body>
        </html>';

        return $html;
    }

    /**
     * Génère une facture PDF pour une commande
     */
    public function generateInvoicePdf($commande, $user): string
    {
        $html = $this->generateInvoiceHtml($commande, $user);
        $filename = 'facture_' . $commande->getId() . '_' . date('Y-m-d') . '.pdf';
        
        return $this->generatePdfFromHtml($html, $filename);
    }
}

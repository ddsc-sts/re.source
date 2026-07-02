-- Dia 2: conversa unica por anuncio/par de empresas e polling incremental.
-- Executar somente em bancos criados antes da atualizacao do schema principal.

ALTER TABLE negotiations
    DROP INDEX idx_neg_unique_pair,
    ADD UNIQUE INDEX idx_neg_unique_pair
        (listing_id, buyer_company_id, seller_company_id);

ALTER TABLE messages
    ADD INDEX idx_msg_polling (negotiation_id, id);

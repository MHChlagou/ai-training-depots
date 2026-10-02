import Link from "next/link";

export default function HomeEnglish() {
  return (
    <section className="page">
      <h1>Equipment and supplies for cafes, hotels and restaurants</h1>
      <p>
        Comptoir is an online wholesaler of equipment and supplies for hospitality
        professionals: espresso machines, grinders, tableware and cleaning
        products. We deliver to France, Belgium and Luxembourg.
      </p>
      <p>
        Our website is currently available in French only.{" "}
        <Link href="/">Visit the French website</Link>.
      </p>
    </section>
  );
}
